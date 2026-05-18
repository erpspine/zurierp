<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItineraryTransport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'itinerary_day_id',
        'type',
        'vehicle',
        'from_location',
        'to_location',
        'distance_km',
        'estimated_time',
        'order',
    ];

    public function day(): BelongsTo
    {
        return $this->belongsTo(ItineraryDay::class, 'itinerary_day_id');
    }
}
