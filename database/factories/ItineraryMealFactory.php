<?php

namespace Database\Factories;

use App\Models\ItineraryMeal;
use App\Models\ItineraryDay;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryMealFactory extends Factory
{
    protected $model = ItineraryMeal::class;

    public function definition(): array
    {
        return [
            'itinerary_day_id' => ItineraryDay::factory(),
            'meal_plan' => $this->faker->randomElement(['Full Board', 'Half Board', 'Breakfast Only']),
            'included_breakfast' => $this->faker->boolean(),
            'included_lunch' => $this->faker->boolean(),
            'included_dinner' => $this->faker->boolean(),
            'dietary_requirements' => $this->faker->optional()->sentence(),
        ];
    }
}
