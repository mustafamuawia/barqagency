<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Airline extends Model
{
    public $timestamps = false;
    protected $fillable = ['code', 'name', 'call_sign', 'is_local', 'gds_enabled', 'channel'];
}
