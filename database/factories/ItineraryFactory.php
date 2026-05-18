<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Itinerary;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryFactory extends Factory
{
    protected $model = Itinerary::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+1 year');
        $endDate = (clone $startDate)->modify('+7 days');

        return [
            'company_id' => Company::factory(),
            'lead_id' => Lead::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration_days' => 7,
            'created_by' => CompanyUser::factory(),
        ];
    }
}
