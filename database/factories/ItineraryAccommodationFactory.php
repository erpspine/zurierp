<?php

namespace Database\Factories;

use App\Models\ItineraryAccommodation;
use App\Models\ItineraryDay;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryAccommodationFactory extends Factory
{
    protected $model = ItineraryAccommodation::class;

    public function definition(): array
    {
        return [
            'itinerary_day_id' => ItineraryDay::factory(),
            'name' => $this->faker->company(),
            'type' => $this->faker->randomElement(['Hotel', 'Lodge', 'Resort', 'Airbnb']),
            'room_preference' => $this->faker->randomElement(['Single', 'Double', 'Twin', 'Suite']),
            'check_in_date' => $this->faker->date(),
            'check_out_date' => $this->faker->date(),
            'notes' => $this->faker->sentence(),
        ];
    }
}
