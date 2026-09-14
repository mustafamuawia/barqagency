<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes, HasUuid;

    protected $fillable = ['uuid', 'order_number', 'agency_id', 'status_id', 'estimated_price', 'total_passengers', 'departure_date', 'return_date', 'trip_type', 'ticket_path', 'ticket_uploaded_at', 'metadata', 'created_by', 'updated_by'];
    protected $casts = ['metadata' => 'json', 'departure_date' => 'date', 'return_date' => 'datetime', 'ticket_uploaded_at' => 'datetime'];
    // The stored path is an implementation detail; clients only need to know a ticket
    // exists (ticket_uploaded_at) and fetch it through the dedicated download endpoint.
    protected $hidden = ['ticket_path'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(TravelAgency::class, 'agency_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(OrderSegment::class)->orderBy('sequence');
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(OrderPassenger::class)->orderBy('sequence');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function latestQuote(): HasOne
    {
        return $this->hasOne(Quote::class)->latestOfMany();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('created_at');
    }

    public function transitionTo(string $statusName, ?string $reason = null): bool
    {
        $newStatus = OrderStatus::where('name', $statusName)->firstOrFail();
        $oldStatusId = $this->status_id;
        $this->update(['status_id' => $newStatus->id]);
        OrderStatusHistory::create([
            'order_id' => $this->id,
            'from_status_id' => $oldStatusId,
            'to_status_id' => $newStatus->id,
            'reason' => $reason,
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);
        return true;
    }

    public function canBePriced(): bool
    {
        return $this->status->name === 'pending_pricing';
    }

    public function canBeApproved(): bool
    {
        return $this->status->name === 'pricing_sent';
    }

    public function canHaveTicketUploaded(): bool
    {
        return in_array($this->status->name, ['approved', 'confirmed'], true);
    }

    public function scopePendingPricing($query)
    {
        return $query->whereHas('status', fn($q) => $q->where('name', 'pending_pricing'));
    }
}
