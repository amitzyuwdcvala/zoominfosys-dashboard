<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use App\Services\Admin\SettingServices;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdNetworkController extends Controller
{
    use ApiResponses;

    public $settingServices;

    public function __construct(SettingServices $settingServices)
    {
        $this->settingServices = $settingServices;
    }

    /**
     * Get Ad Settings for Mobile App (cached 5 minutes).
     */
    public function get_ads_settings()
    {
        try {
            $settings = Cache::remember(
                SettingServices::CACHE_KEY_ADS_SETTINGS,
                SettingServices::CACHE_TTL_ADS_SETTINGS,
                function () {
                    return \App\Models\Setting::all();
                }
            );

            return $this->okResponse([
                'message' => 'Ad settings fetched successfully',
                'data' => $settings,
            ]);
        } catch (\Exception $e) {
            Log::error('get_ads_settings_error', [
                'message' => $e->getMessage(),
            ]);
            return $this->errorResponse([], __('Something went wrong'), 500);
        }
    }
}
