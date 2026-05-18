<?php

namespace Database\Factories;

use App\Models\ItineraryActivity;
use App\Models\ItineraryDay;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryActivityFactory extends Factory
{
    protected $model = ItineraryActivity::class;

    public function definition(): array
    {
        return [
            'itinerary_day_id' => ItineraryDay::factory(),
            'type' => $this->faker->randomElement(['Game Drive', 'Walking', 'Water Sports', 'Cultural']),
            'name' => $this->faker->sentence(2),
            'time' => $this->faker->time(),
            'cost_usd' => $this->faker->randomFloat(2, 50, 500),
            'order' => $this->faker->numberBetween(1, 5),
        ];
    }
}
