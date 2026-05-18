<?php

namespace Database\Factories;

use App\Models\ItineraryTransport;
use App\Models\ItineraryDay;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryTransportFactory extends Factory
{
    protected $model = ItineraryTransport::class;

    public function definition(): array
    {
        return [
            'itinerary_day_id' => ItineraryDay::factory(),
            'type' => $this->faker->randomElement(['Road Transfer', 'Walking', 'Flight', 'Boat']),
            'vehicle' => $this->faker->randomElement(['4x4 Land Cruiser', 'Safari Van', 'Private Car', 'Boat']),
            'from_location' => $this->faker->city(),
            'to_location' => $this->faker->city(),
            'distance_km' => $this->faker->randomFloat(1, 10, 200),
            'estimated_time' => $this->faker->time(),
            'order' => $this->faker->numberBetween(1, 5),
        ];
    }
}
