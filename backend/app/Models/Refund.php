<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid', 'refund_number', 'order_id', 'agency_id', 'status_id',
        'original_amount', 'penalty_amount', 'refund_amount', 'currency_code',
        'reason', 'admin_remarks', 'requested_by', 'decided_by', 'requested_at', 'decided_at',
    ];

    protected $casts = [
        'original_amount' => 'decimal:3',
        'penalty_amount' => 'decimal:3',
        'refund_amount' => 'decimal:3',
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(TravelAgency::class, 'agency_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(RefundStatus::class, 'status_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function scopePending($query)
    {
        return $query->whereHas('status', fn($q) => $q->where('name', 'pending'));
    }
}
