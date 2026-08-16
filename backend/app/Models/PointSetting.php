<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointSetting extends Model
{
    protected $fillable = ['money_amount', 'point_amount', 'updated_by'];
    protected $casts = ['money_amount' => 'decimal:3', 'point_amount' => 'decimal:3'];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
