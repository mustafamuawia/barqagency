<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use SoftDeletes, HasUuid;

    protected $fillable = ['uuid', 'order_id', 'status_id', 'base_price', 'taxes', 'fees', 'total_price', 'currency_code', 'remarks', 'valid_until', 'pricing_details', 'created_by'];
    protected $casts = ['base_price' => 'decimal:3', 'taxes' => 'decimal:3', 'fees' => 'decimal:3', 'total_price' => 'decimal:3', 'valid_until' => 'datetime', 'pricing_details' => 'json'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function status(): BelongsTo { return $this->belongsTo(QuoteStatus::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function isValid(): bool
    {
        return $this->valid_until === null || $this->valid_until->isFuture();
    }
}
