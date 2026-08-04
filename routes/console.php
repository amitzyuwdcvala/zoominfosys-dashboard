<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Subscription expiry is now handled on-the-fly inside AuthService::register_service
// (runs every time user opens the app). No scheduled cron needed.
// To manually expire stale VIP users from CLI, run:
//   php artisan subscriptions:check-expiration
