<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderSegment extends Model
{
    protected $fillable = ['order_id', 'airline_id', 'departure_airport_id', 'arrival_airport_id', 'cabin_class_id', 'departure_datetime', 'departure_terminal', 'arrival_datetime', 'flight_number', 'aircraft_type', 'duration_minutes', 'sequence', 'metadata'];
    protected $casts = ['departure_datetime' => 'datetime', 'arrival_datetime' => 'datetime', 'metadata' => 'json'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function airline(): BelongsTo { return $this->belongsTo(Airline::class); }
    public function departureAirport(): BelongsTo { return $this->belongsTo(Airport::class, 'departure_airport_id'); }
    public function arrivalAirport(): BelongsTo { return $this->belongsTo(Airport::class, 'arrival_airport_id'); }
    public function cabinClass(): BelongsTo { return $this->belongsTo(CabinClass::class); }
}
