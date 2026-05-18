<?php

namespace Database\Factories;

use App\Models\ItineraryImage;
use App\Models\ItineraryDay;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItineraryImageFactory extends Factory
{
    protected $model = ItineraryImage::class;

    public function definition(): array
    {
        return [
            'itinerary_day_id' => ItineraryDay::factory(),
            'image_path' => $this->faker->imageUrl(),
            'caption' => $this->faker->sentence(),
            'order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
