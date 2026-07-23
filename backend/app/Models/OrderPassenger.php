<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPassenger extends Model
{
    protected $fillable = ['order_id', 'passenger_type_id', 'title', 'first_name', 'last_name', 'date_of_birth', 'passport_number', 'passport_expiry', 'nationality', 'sequence', 'metadata'];
    protected $casts = [
        'date_of_birth'   => 'date',
        'passport_expiry' => 'date',
        'metadata'        => 'json',
        'passport_number' => 'encrypted',
        'first_name'      => 'encrypted',
        'last_name'       => 'encrypted',
    ];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function passengerType(): BelongsTo { return $this->belongsTo(PassengerType::class); }
}
