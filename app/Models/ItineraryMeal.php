<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItineraryMeal extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'itinerary_day_id',
        'meal_plan',
        'included_breakfast',
        'included_lunch',
        'included_dinner',
        'dietary_requirements',
    ];

    protected function casts(): array
    {
        return [
            'included_breakfast' => 'boolean',
            'included_lunch' => 'boolean',
            'included_dinner' => 'boolean',
        ];
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(ItineraryDay::class, 'itinerary_day_id');
    }
}
