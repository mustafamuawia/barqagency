<?php

namespace App\Models;

use App\Exceptions\ImmutableRecordException;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WalletTransaction extends Model
{
    use HasUuid;

    public $timestamps = false;

    protected $fillable = ['uuid', 'wallet_id', 'transaction_type_id', 'amount', 'money_equivalent', 'balance_after', 'reference_type', 'reference_id', 'description', 'metadata', 'created_by', 'created_at'];
    protected $casts = ['amount' => 'decimal:3', 'money_equivalent' => 'decimal:3', 'balance_after' => 'decimal:3', 'metadata' => 'json', 'created_at' => 'datetime'];
    protected $appends = ['receipt_url'];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new ImmutableRecordException('لا يمكن تعديل سجلات المعاملات المالية بعد إنشائها');
        });

        static::deleting(function () {
            throw new ImmutableRecordException('لا يمكن حذف سجلات المعاملات المالية');
        });
    }

    public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class, 'wallet_id'); }
    public function transactionType(): BelongsTo { return $this->belongsTo(WalletTransactionType::class, 'transaction_type_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    /** Absolute, ready-to-use URL for the deposit receipt (if any), regardless of where on the public disk it lives. */
    public function getReceiptUrlAttribute(): ?string
    {
        $path = $this->metadata['receipt_path'] ?? null;

        return $path ? Storage::disk('public')->url($path) : null;
    }
}
