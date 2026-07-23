<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundStatus extends Model
{
    protected $fillable = ['name', 'label'];

    public function refunds()
    {
        return $this->hasMany(Refund::class, 'status_id');
    }
}
