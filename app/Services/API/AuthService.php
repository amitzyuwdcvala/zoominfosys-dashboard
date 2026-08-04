<?php

namespace App\Services\API;

use App\Http\Traits\ApiResponses;
use App\Models\AppConfig;
use App\Models\User;
use App\Models\UserSubscription;
use App\Constants\SubscriptionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AuthService
{
    use ApiResponses;

    /**
     * Register user (first time app launch).
     * Also checks and expires VIP subscription on-the-fly so cron is not needed.
     */
    public function register_service($request)
    {
        try {
            DB::beginTransaction();

            $androidId = $request->header('X-Android-Id') ?? $request->header('X-Android-ID') ?? $request->input('android_id');

            $user = User::find($androidId);

            if ($user) {
                $this->expireIfNeeded($user);

                DB::commit();

                return $this->successResponse([
                    'message' => 'User already registered',
                    'data' => [
                        'user'   => $this->buildUserPayload($user),
                        'config' => AppConfig::getDecodedCached(),
                    ],
                ]);
            }

            // Create new user
            $user = User::create([
                'android_id'        => $androidId,
                'is_vip'            => false,
                'video_click_count' => 5,
            ]);

            DB::commit();

            return $this->successResponse([
                'message' => 'User registered successfully',
                'data' => [
                    'user'   => $this->buildUserPayload($user),
                    'config' => AppConfig::getDecodedCached(),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AuthService register_service error: ' . $e->getMessage());

            return $this->errorResponse([], 'Registration failed. Please try again.', 500);
        }
    }

    /**
     * Check if user's VIP subscription has expired and mark it expired immediately.
     * Called on every register (app open) so cron is not needed.
     */
    private function expireIfNeeded(User $user): void
    {
        // Only VIP users need checking
        if (!$user->is_vip) {
            return;
        }

        $subscription = $user->subscriptions; // hasOne — latest subscription row

        if (!$subscription) {
            // VIP flag set but no subscription row — clean up
            $user->is_vip = false;
            $user->save();
            \App\Services\API\VideoAccessService::invalidateVipAccessCache($user->android_id);
            return;
        }

        // Check if subscription end date has passed
        $expired = false;

        if ($subscription->end_at && $subscription->end_at < now()) {
            $expired = true;
        } elseif (!$subscription->end_at && $subscription->end_date && $subscription->end_date < now()->toDateString()) {
            $expired = true;
        }

        if (!$expired) {
            return; 
        }

        if ($subscription->status !== SubscriptionStatus::EXPIRED) {
            $subscription->status = SubscriptionStatus::EXPIRED;
            $subscription->save();
        }

        $user->is_vip = false;
        $user->save();

        \App\Services\API\VideoAccessService::invalidateVipAccessCache($user->android_id);
    }

    /**
     * Build user payload including active subscription details (if any).
     */
    protected function buildUserPayload(User $user): array
    {
        $payload = [
            'android_id'        => $user->android_id,
            'is_vip'            => $user->is_vip,
            'video_click_count' => $user->video_click_count,
            'subscription'      => null,
        ];

        // Only include subscription details if there is an active subscription
        $subscription = $user->activeSubscription()->with('plan')->first();

        if ($subscription && $subscription->plan) {
            $now = Carbon::now()->startOfDay();
            $end = $subscription->end_date
                ? Carbon::parse($subscription->end_date)->startOfDay()
                : null;

            $remainingDays = null;
            if ($end && $end->gte($now)) {
                $remainingDays = (int) $now->diffInDays($end);
            }

            $plan = $subscription->plan;

            $payload['subscription'] = [
                'id'             => $subscription->id,
                'status'         => $subscription->status,
                'start_date'     => $subscription->start_date?->format('Y-m-d'),
                'end_date'       => $subscription->end_date?->format('Y-m-d'),
                'remaining_days' => $remainingDays,
                'plan' => [
                    'id'       => $plan->id,
                    'name'     => $plan->name,
                    'amount'   => (float) $plan->amount,
                    'days'     => (int) $plan->days,
                    'features' => $plan->features ?? [],
                    'currency' => 'INR',
                ],
            ];
        }

        return $payload;
    }
}
