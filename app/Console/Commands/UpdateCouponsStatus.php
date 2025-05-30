<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Coupon;
use Illuminate\Support\Facades\Log;

class UpdateCouponsStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'coupons:update-status';

    /**
     * The console command description.
     */
    protected $description = 'Automatically update coupon statuses based on validity and usage limits.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Log::info('Running UpdateCouponsStatus command...');
        Coupon::autoUpdateCouponsStatus();
        Log::info('Coupon statuses updated successfully.');
        $this->info('Coupon statuses updated successfully.');
    }
}
