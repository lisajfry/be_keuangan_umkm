<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'umkm_id',
        'date',
        'description',
        'category',
        'cash_flow_category',
        'is_dividend',
    ];

    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function umkm()
{
    return $this->belongsTo(Umkm::class, 'umkm_id');
}

}
