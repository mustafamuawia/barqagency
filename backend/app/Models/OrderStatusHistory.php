<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'from_status_id', 'to_status_id', 'reason', 'metadata', 'created_by', 'created_at'];
    protected $casts = ['metadata' => 'json', 'created_at' => 'datetime'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function fromStatus(): BelongsTo { return $this->belongsTo(OrderStatus::class, 'from_status_id'); }
    public function toStatus(): BelongsTo { return $this->belongsTo(OrderStatus::class, 'to_status_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
