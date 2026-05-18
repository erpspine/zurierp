<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Lead;
use App\Models\Itinerary;
use App\Models\ItineraryDay;
use App\Models\ItineraryActivity;
use App\Models\ItineraryTransport;
use App\Models\ItineraryMeal;
use App\Models\ItineraryAccommodation;
use App\Models\ItineraryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantItineraryApiTest extends TestCase
{
    use RefreshDatabase;

    private CompanyUser $user;
    private Company $company;
    private Itinerary $itinerary;
    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = CompanyUser::factory()->create(['company_id' => $this->company->id]);
        $this->lead = Lead::factory()->create(['company_id' => $this->company->id]);
        $this->itinerary = Itinerary::factory()->create([
            'company_id' => $this->company->id,
            'lead_id' => $this->lead->id,
            'created_by' => $this->user->id,
            'name' => 'Sample Itinerary',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(7),
            'duration_days' => 7,
        ]);
    }

    public function test_list_itineraries_with_pagination()
    {
        Itinerary::factory(5)->create(['company_id' => $this->company->id]);

        $response = $this->actingAs($this->user, 'tenant')
            ->getJson('/api/app/itineraries');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'company_id', 'name', 'start_date', 'end_date'],
                ],
            ])
            ->assertJsonCount(6, 'data'); // 1 from setUp + 5 from factory
    }

    public function test_itinerary_dashboard_returns_summary_and_widgets()
    {
        Itinerary::factory()->create([
            'company_id' => $this->company->id,
            'lead_id' => $this->lead->id,
            'created_by' => $this->user->id,
            'name' => 'Confirmed Future Itinerary',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(14),
        ]);

        $ongoing = Itinerary::factory()->create([
            'company_id' => $this->company->id,
            'lead_id' => $this->lead->id,
            'created_by' => $this->user->id,
            'name' => 'Ongoing Itinerary',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(2),
        ]);

        ItineraryDay::factory()->create([
            'itinerary_id' => $ongoing->id,
            'day_number' => 1,
            'title' => 'Day 1',
        ]);

        Itinerary::factory()->create([
            'company_id' => $this->company->id,
            'lead_id' => $this->lead->id,
            'created_by' => $this->user->id,
            'name' => 'Completed Itinerary',
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(3),
        ]);

        $response = $this->actingAs($this->user, 'tenant')
            ->getJson('/api/app/itineraries/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'summary' => [
                    'total_itineraries',
                    'draft',
                    'confirmed',
                    'in_use',
                    'completed',
                ],
                'itineraries_by_status',
                'upcoming_itineraries',
                'top_destinations',
                'recent_activities',
                'itineraries_by_month',
                'generated_at',
            ])
            ->assertJsonPath('summary.total_itineraries', 4)
            ->assertJsonPath('summary.completed', 1);
    }

    public function test_create_itinerary()
    {
        $payload = [
            'lead_id' => $this->lead->id,
            'name' => 'Kenya Safari Adventure',
            'description' => 'A week-long safari adventure in Kenya',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'duration_days' => 7,
        ];

        $response = $this->actingAs($this->user, 'tenant')
            ->postJson('/api/app/itineraries', $payload);

        $response->assertCreated()
            ->assertJsonPath('message', 'Itinerary created successfully.')
            ->assertJsonPath('itinerary.name', 'Kenya Safari Adventure');

        $this->assertDatabaseHas('itineraries', ['name' => 'Kenya Safari Adventure', 'lead_id' => (string) $this->lead->id]);
    }

    public function test_show_itinerary_with_nested_data()
    {
        $day = ItineraryDay::factory()->create(['itinerary_id' => $this->itinerary->id]);
        $activity = ItineraryActivity::factory()->create(['itinerary_day_id' => $day->id]);
        $transport = ItineraryTransport::factory()->create(['itinerary_day_id' => $day->id]);
        $meal = ItineraryMeal::factory()->create(['itinerary_day_id' => $day->id]);

        $response = $this->actingAs($this->user, 'tenant')
            ->getJson("/api/app/itineraries/{$this->itinerary->id}");

        $response->assertOk()
            ->assertJsonPath('id', (string) $this->itinerary->id)
            ->assertJsonPath('name', $this->itinerary->name)
            ->assertJsonStructure([
                'id', 'company_id', 'name', 'days' => [
                    '*' => [
                        'id', 'day_number', 'title',
                        'accommodations', 'activities', 'transports', 'meals', 'images',
                    ],
                ],
            ]);
    }

    public function test_update_itinerary()
    {
        $updatePayload = [
            'name' => 'Updated Itinerary Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->user, 'tenant')
            ->putJson("/api/app/itineraries/{$this->itinerary->id}", $updatePayload);

        $response->assertOk()
            ->assertJsonPath('message', 'Itinerary updated successfully.')
            ->assertJsonPath('itinerary.name', 'Updated Itinerary Name');

        $this->assertDatabaseHas('itineraries', ['id' => (string) $this->itinerary->id, 'name' => 'Updated Itinerary Name']);
    }

    public function test_delete_itinerary()
    {
        $response = $this->actingAs($this->user, 'tenant')
            ->deleteJson("/api/app/itineraries/{$this->itinerary->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Itinerary deleted successfully.');

        $this->assertDatabaseMissing('itineraries', ['id' => (string) $this->itinerary->id]);
    }

    public function test_cannot_access_other_company_itinerary()
    {
        $otherCompany = Company::factory()->create();
        $otherItinerary = Itinerary::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($this->user, 'tenant')
            ->getJson("/api/app/itineraries/{$otherItinerary->id}");

        $response->assertNotFound();
    }

    public function test_create_itinerary_validates_dates()
    {
        $payload = [
            'name' => 'Invalid Itinerary',
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDay()->toDateString(), // end_date before start_date
        ];

        $response = $this->actingAs($this->user, 'tenant')
            ->postJson('/api/app/itineraries', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_unauthorized_user_cannot_access_itineraries()
    {
        $response = $this->getJson('/api/app/itineraries');

        $response->assertUnauthorized();
    }
}
