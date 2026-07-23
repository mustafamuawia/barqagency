<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Airport extends Model
{
    public $timestamps = false;
    protected $fillable = ['code', 'name', 'city_id', 'country_id'];

    public function city(): BelongsTo { return $this->belongsTo(City::class); }
    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
}
