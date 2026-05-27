<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Trial',
                'slug' => 'trial',
                'subtitle' => 'Free access for the first 30 days',
                'monthly_price' => 0,
                'is_custom_pricing' => false,
                'users_limit' => 2,
                'branches_limit' => 1,
                'vehicles_limit' => 2,
                'bookings_limit' => 15,
                'features' => [
                    '30 days free access',
                    'CRM / Leads',
                    'Itinerary Builder',
                    'Quotations',
                    'Basic Bookings',
                    'Email support',
                ],
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'subtitle' => 'For small tour operators',
                'monthly_price' => 49,
                'is_custom_pricing' => false,
                'users_limit' => 3,
                'branches_limit' => 1,
                'vehicles_limit' => 5,
                'bookings_limit' => 30,
                'features' => [
                    'CRM / Leads',
                    'Itinerary Builder',
                    'Quotations',
                    'Basic Costing',
                    'Bookings',
                    'Basic Reports',
                ],
                'is_featured' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'subtitle' => 'Best package for most companies',
                'monthly_price' => 99,
                'is_custom_pricing' => false,
                'users_limit' => 10,
                'branches_limit' => 2,
                'vehicles_limit' => 20,
                'bookings_limit' => 150,
                'features' => [
                    'Everything in Starter',
                    'Operations Checklist',
                    'Fleet Management',
                    'Supplier Management',
                    'Supplier Rates',
                    'Finance: Invoices, Receipts, Payments',
                    'Profitability Reports',
                ],
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'subtitle' => 'For growing companies',
                'monthly_price' => 199,
                'is_custom_pricing' => false,
                'users_limit' => 25,
                'branches_limit' => 5,
                'vehicles_limit' => 50,
                'bookings_limit' => 500,
                'features' => [
                    'Everything in Professional',
                    'Full Finance Module',
                    'Chart of Accounts',
                    'General Ledger',
                    'Cash Book',
                    'P&L, Balance Sheet, Trial Balance',
                    'Bank Reconciliation',
                    'Multi-currency',
                    'WhatsApp Notifications',
                ],
                'is_featured' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'subtitle' => 'For large operators',
                'monthly_price' => null,
                'is_custom_pricing' => true,
                'users_limit' => null,
                'branches_limit' => null,
                'vehicles_limit' => null,
                'bookings_limit' => null,
                'features' => [
                    'Everything in Business',
                    'Dedicated database option',
                    'Custom reports',
                    'API integrations',
                    'Advanced permissions',
                    'Priority support',
                    'Custom onboarding',
                ],
                'is_featured' => false,
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
