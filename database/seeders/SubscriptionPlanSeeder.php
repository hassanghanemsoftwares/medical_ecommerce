<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionPlan::query()->delete();

        SubscriptionPlan::create([
            'name' => 'Monthly Plan',
            'price' => 9.99,
            'duration' => 'monthly',
            'is_active' => true,
        ]);

        SubscriptionPlan::create([
            'name' => 'Yearly Plan',
            'price' => 99.99,
            'duration' => 'yearly',
            'is_active' => true,
        ]);
    }
}
