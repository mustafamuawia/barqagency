<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockFlightOffer extends Model
{
    protected $fillable = [
        'uuid',
        'airline_id',
        'origin_airport_id',
        'destination_airport_id',
        'cabin_class_id',
        'price_oneway',
        'price_roundtrip',
        'currency',
        'duration_minutes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'price_oneway' => 'decimal:2',
            'price_roundtrip' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class);
    }

    public function originAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'origin_airport_id');
    }

    public function destinationAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'destination_airport_id');
    }

    public function cabinClass(): BelongsTo
    {
        return $this->belongsTo(CabinClass::class);
    }
}
