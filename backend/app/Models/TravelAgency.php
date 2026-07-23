<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TravelAgency extends Model
{
    use SoftDeletes, HasUuid;

    protected $table = 'travel_agencies';
    protected $fillable = ['name', 'email', 'phone', 'address', 'agency_type_id', 'license_number', 'tax_id', 'status', 'credit_limit', 'metadata', 'created_by', 'updated_by', 'deleted_by'];
    protected $casts = [
        'metadata' => 'json',
        'phone'    => 'encrypted',
    ];

    public function agencyType(): BelongsTo
    {
        return $this->belongsTo(AgencyType::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'agency_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'agency_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(AgencyUser::class, 'agency_id');
    }
}
