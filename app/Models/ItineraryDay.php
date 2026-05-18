<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItineraryDay extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'itinerary_id',
        'day_number',
        'title',
        'date',
        'day_highlights',
        'detailed_description',
        'overnight_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function accommodations(): HasMany
    {
        return $this->hasMany(ItineraryAccommodation::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ItineraryActivity::class)->orderBy('order');
    }

    public function transports(): HasMany
    {
        return $this->hasMany(ItineraryTransport::class)->orderBy('order');
    }

    public function meals(): HasMany
    {
        return $this->hasMany(ItineraryMeal::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ItineraryImage::class);
    }
}
