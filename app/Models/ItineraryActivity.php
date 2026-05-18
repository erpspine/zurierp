<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItineraryActivity extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'itinerary_day_id',
        'type',
        'name',
        'time',
        'cost_usd',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'cost_usd' => 'float',
        ];
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(ItineraryDay::class, 'itinerary_day_id');
    }
}
