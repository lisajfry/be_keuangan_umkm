<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = ['code','name','type','is_cash'];

    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
