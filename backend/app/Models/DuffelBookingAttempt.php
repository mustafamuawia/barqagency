<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuffelBookingAttempt extends Model
{
    use HasUuid;

    protected $fillable = [
        'agency_id',
        'created_by',
        'offer_id',
        'idempotency_key',
        'duffel_passengers',
        'duffel_amount',
        'duffel_currency',
        'sar_amount',
        'status',
        'order_id',
        'attempts',
        'last_attempted_at',
        'resolved_at',
    ];

    protected $casts = [
        'duffel_passengers' => 'json',
        'sar_amount'        => 'decimal:3',
        'last_attempted_at' => 'datetime',
        'resolved_at'       => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(TravelAgency::class, 'agency_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    // Duffel idempotency keys are honoured for 24 hours from the first request.
    public function isWithinIdempotencyWindow(): bool
    {
        return $this->created_at->gt(now()->subHours(24));
    }
}
