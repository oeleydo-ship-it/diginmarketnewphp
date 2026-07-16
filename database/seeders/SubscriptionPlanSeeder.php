<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Starter', 'slug' => 'starter', 'price' => 0, 'billing_period' => 'lifetime', 'commission_rate' => null, 'listing_limit' => 5, 'features' => ['Up to 5 active listings', 'Standard commission rate', 'Community support'], 'sort_order' => 0],
            ['name' => 'Pro Monthly', 'slug' => 'pro-monthly', 'price' => 19.00, 'billing_period' => 'monthly', 'commission_rate' => 12.00, 'listing_limit' => 50, 'features' => ['Up to 50 active listings', 'Reduced 12% commission', 'Priority support'], 'sort_order' => 1],
            ['name' => 'Pro Yearly', 'slug' => 'pro-yearly', 'price' => 190.00, 'billing_period' => 'yearly', 'commission_rate' => 10.00, 'listing_limit' => 200, 'features' => ['Up to 200 active listings', 'Reduced 10% commission', 'Priority support', 'Two months free'], 'sort_order' => 2],
            ['name' => 'Lifetime', 'slug' => 'lifetime', 'price' => 499.00, 'billing_period' => 'lifetime', 'commission_rate' => 8.00, 'listing_limit' => null, 'features' => ['Unlimited listings', 'Lowest 8% commission', 'Priority support', 'Pay once, keep forever'], 'sort_order' => 3],
        ];
        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan + ['is_active' => true]);
        }
    }
}
