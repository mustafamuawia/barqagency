<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassengerType extends Model
{
    public $timestamps = false;
    protected $fillable = ['code', 'name'];
}
