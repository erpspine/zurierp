<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $travelStartDate = $this->faker->dateTimeBetween('now', '+1 year');
        $travelEndDate = (clone $travelStartDate)->modify('+7 days');

        return [
            'company_id' => Company::factory(),
            'customer_id' => $this->faker->unique()->bothify('CUST-####'),
            'lead_id' => $this->faker->uuid(),
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone_whatsapp' => $this->faker->phoneNumber(),
            'country' => $this->faker->country(),
            'city' => $this->faker->city(),
            'lead_source' => $this->faker->randomElement(['Website', 'Email', 'Phone', 'Referral', 'Social Media']),
            'travel_start_date' => $travelStartDate->format('Y-m-d'),
            'travel_end_date' => $travelEndDate->format('Y-m-d'),
            'number_of_days' => 7,
            'number_of_nights' => 6,
            'number_of_pax' => $this->faker->numberBetween(1, 8),
            'adults' => $this->faker->numberBetween(1, 6),
            'children' => $this->faker->numberBetween(0, 3),
            'infants' => $this->faker->numberBetween(0, 2),
            'preferred_destinations' => [
                $this->faker->randomElement(['Kenya', 'Tanzania', 'Uganda', 'Rwanda']),
                $this->faker->randomElement(['Safari', 'Mountains', 'Beach', 'Cultural']),
            ],
            'trip_type' => $this->faker->randomElement(['Safari', 'Beach', 'Hiking', 'Cultural', 'Adventure']),
            'residency_type' => $this->faker->randomElement(['Resident', 'Tourist', 'Expatriate']),
            'budget_range' => $this->faker->randomElement(['Budget', 'Mid-Range', 'Luxury', 'Ultra-Luxury']),
            'estimated_budget_amount' => $this->faker->randomFloat(2, 5000, 50000),
            'preferred_vehicle' => $this->faker->randomElement(['4x4', 'Bus', 'Van', 'Private Car']),
            'accommodation_type' => $this->faker->randomElement(['Hotel', 'Lodge', 'Resort', 'Tented Camp']),
            'room_preference' => $this->faker->randomElement(['Single', 'Double', 'Twin', 'Suite']),
            'meal_plan' => $this->faker->randomElement(['All Inclusive', 'Half Board', 'Bed and Breakfast']),
            'activities_interested_in' => [
                $this->faker->randomElement(['Game Drive', 'Walking Safari', 'Boat Safari', 'Photography']),
            ],
            'special_interests' => $this->faker->sentence(),
            'dietary_requirement' => $this->faker->randomElement(['Vegetarian', 'Vegan', 'Halal', 'None']),
            'language_preference' => $this->faker->randomElement(['English', 'French', 'German', 'Spanish']),
            'guide_preference' => $this->faker->randomElement(['English Speaking', 'Bilingual', 'Multilingual']),
            'lead_status' => $this->faker->randomElement(['New', 'Qualified', 'Negotiating', 'Won', 'Lost']),
            'assigned_sales_person_id' => CompanyUser::factory(),
            'priority' => $this->faker->randomElement(['Low', 'Medium', 'High', 'Critical']),
            'follow_up_date' => $this->faker->dateTimeBetween('now', '+7 days')->format('Y-m-d'),
            'follow_up_time' => $this->faker->time('H:i:s'),
            'next_action' => $this->faker->sentence(),
            'quotation_status' => $this->faker->randomElement(['Pending', 'Sent', 'Accepted', 'Rejected']),
            'probability_of_winning' => $this->faker->numberBetween(10, 100),
            'client_request_summary' => $this->faker->paragraph(),
            'passport_visa_notes' => $this->faker->paragraph(),
            'internal_sales_notes' => $this->faker->paragraph(),
            'payment_special_conditions' => $this->faker->paragraph(),
            'uploaded_documents' => [],
        ];
    }
}
