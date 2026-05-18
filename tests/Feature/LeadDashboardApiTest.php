<?php

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('tenant lead dashboard returns aggregated metrics', function (): void {
    $company = Company::query()->create([
        'name' => 'Sher Tours',
    ]);

    $user = CompanyUser::query()->create([
        'company_id' => $company->id,
        'name' => 'Robin Joshua',
        'email' => 'robin@example.com',
        'password' => Hash::make('password123'),
        'status' => 'active',
    ]);

    $this->actingAs($user, 'tenant');

    $leadOne = Lead::query()->create([
        'company_id' => $company->id,
        'customer_id' => 'CUST-0001',
        'lead_id' => 'LD-0001',
        'full_name' => 'Emma Johnson',
        'email' => 'emma@example.com',
        'phone_whatsapp' => '+255700000001',
        'country' => 'Tanzania',
        'lead_source' => 'Website',
        'travel_start_date' => now()->addMonth()->toDateString(),
        'travel_end_date' => now()->addMonth()->addDays(5)->toDateString(),
        'preferred_destinations' => ['Zanzibar', 'Arusha'],
        'trip_type' => 'Leisure',
        'residency_type' => 'Tourist',
        'budget_range' => 'Premium',
        'accommodation_type' => 'Hotel',
        'activities_interested_in' => ['Safari'],
        'lead_status' => 'new',
        'assigned_sales_person_id' => $user->id,
        'priority' => 'high',
        'follow_up_date' => today()->toDateString(),
        'follow_up_time' => '10:00',
        'next_action' => 'Send itinerary',
        'quotation_status' => 'draft',
    ]);

    $leadTwo = Lead::query()->create([
        'company_id' => $company->id,
        'customer_id' => 'CUST-0002',
        'lead_id' => 'LD-0002',
        'full_name' => 'Michael Brown',
        'email' => 'michael@example.com',
        'phone_whatsapp' => '+255700000002',
        'country' => 'Kenya',
        'lead_source' => 'WhatsApp',
        'travel_start_date' => now()->addWeeks(2)->toDateString(),
        'travel_end_date' => now()->addWeeks(2)->addDays(4)->toDateString(),
        'preferred_destinations' => ['Serengeti'],
        'trip_type' => 'Safari',
        'residency_type' => 'Resident',
        'budget_range' => 'Standard',
        'accommodation_type' => 'Lodge',
        'activities_interested_in' => ['Game Drive'],
        'lead_status' => 'won',
        'assigned_sales_person_id' => $user->id,
        'priority' => 'medium',
        'follow_up_date' => today()->toDateString(),
        'follow_up_time' => '12:30',
        'next_action' => 'Confirm payment',
        'quotation_status' => 'sent',
    ]);

    $leadThree = Lead::query()->create([
        'company_id' => $company->id,
        'customer_id' => 'CUST-0003',
        'lead_id' => 'LD-0003',
        'full_name' => 'Sarah Davis',
        'email' => 'sarah@example.com',
        'phone_whatsapp' => '+255700000003',
        'country' => 'Uganda',
        'lead_source' => 'Website',
        'travel_start_date' => now()->addMonths(2)->toDateString(),
        'travel_end_date' => now()->addMonths(2)->addDays(6)->toDateString(),
        'preferred_destinations' => ['Zanzibar'],
        'trip_type' => 'Holiday',
        'residency_type' => 'Tourist',
        'budget_range' => 'Luxury',
        'accommodation_type' => 'Resort',
        'activities_interested_in' => ['Beach'],
        'lead_status' => 'contacted',
        'assigned_sales_person_id' => $user->id,
        'priority' => 'low',
        'follow_up_date' => today()->toDateString(),
        'follow_up_time' => '14:00',
        'next_action' => 'Call back',
        'quotation_status' => 'sent',
    ]);

    Lead::query()->whereKey($leadTwo->id)->update([
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    Lead::query()->whereKey($leadThree->id)->update([
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ]);

    AuditLog::query()->create([
        'actor_guard' => 'tenant',
        'actor_id' => $user->id,
        'company_id' => $company->id,
        'action' => 'lead.created',
        'auditable_type' => Lead::class,
        'auditable_id' => $leadOne->id,
        'event_data' => [
            'full_name' => $leadOne->full_name,
            'lead_reference' => $leadOne->lead_id,
        ],
    ]);

    $response = $this->getJson('/api/app/leads/dashboard');

    $response
        ->assertOk()
        ->assertJsonPath('summary.total_leads', 3)
        ->assertJsonPath('summary.new_leads_today', 1)
        ->assertJsonPath('summary.quotations_sent', 2)
        ->assertJsonPath('summary.won_leads', 1)
        ->assertJsonPath('summary.conversion_rate', 33.3)
        ->assertJsonPath('follow_ups_due_today.0.full_name', 'Emma Johnson')
        ->assertJsonPath('recent_activities.0.full_name', 'Emma Johnson');
});