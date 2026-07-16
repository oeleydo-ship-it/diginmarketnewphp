<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(\App\Services\SellerWalletService::class)->clearEligible())
    ->name('seller-earnings-clearance')
    ->dailyAt('01:00')
    ->withoutOverlapping();

Schedule::command('marketplace:backup')
    ->dailyAt('02:30')
    ->withoutOverlapping();

Schedule::call(fn () => app(\App\Services\SubscriptionService::class)->expireDue())
    ->name('expire-seller-subscriptions')
    ->hourly()
    ->withoutOverlapping();
