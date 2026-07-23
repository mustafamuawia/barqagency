<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransactionType extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'label'];
}
