<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SubscriptionPlan::firstOrCreate(
            ['name' => 'Monthly'],
            [
                'price' => 1000,
                'duration_days' => 30,
                'is_active' => true,
            ]
        );
    }
}
