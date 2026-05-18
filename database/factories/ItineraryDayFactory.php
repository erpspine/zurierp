<?php

namespace Database\Factories;

use App\Models\ItineraryDay;
use App\Models\Itinerary;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryDayFactory extends Factory
{
    protected $model = ItineraryDay::class;

    public function definition(): array
    {
        return [
            'itinerary_id' => Itinerary::factory(),
            'day_number' => $this->faker->numberBetween(1, 7),
            'title' => $this->faker->sentence(2),
            'date' => $this->faker->date(),
            'day_highlights' => $this->faker->sentence(),
            'detailed_description' => $this->faker->paragraph(),
            'overnight_at' => $this->faker->city(),
        ];
    }
}
