<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyUser extends Model
{
    protected $fillable = ['agency_id', 'user_id', 'role'];

    public function agency(): BelongsTo { return $this->belongsTo(TravelAgency::class, 'agency_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
