<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\UpdateCouponsStatus;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('coupons:update-status', function () {
    $this->call(UpdateCouponsStatus::class);
})->describe('Automatically update coupon statuses based on validity and usage limits.');
