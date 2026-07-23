<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasUuid;

    protected $fillable = ['uuid', 'agency_id', 'balance', 'paid_balance', 'unpaid_balance', 'credit_limit', 'metadata', 'created_by', 'updated_by'];
    protected $casts = ['balance' => 'decimal:3', 'paid_balance' => 'decimal:3', 'unpaid_balance' => 'decimal:3', 'credit_limit' => 'decimal:3', 'metadata' => 'json'];
    protected $appends = ['status'];

    public function agency(): BelongsTo { return $this->belongsTo(TravelAgency::class, 'agency_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function transactions(): HasMany { return $this->hasMany(WalletTransaction::class)->orderByDesc('created_at'); }

    public function canDebit(float $amount): bool
    {
        return ($this->balance - $amount) >= -$this->credit_limit;
    }

    public function getStatusAttribute(): string
    {
        if ($this->balance < -($this->credit_limit * 0.8)) return 'critical';
        if ($this->balance < -($this->credit_limit * 0.5)) return 'warning';
        if ($this->balance < 0) return 'negative';
        return 'healthy';
    }
}
