<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions'; // 🟢 pakai tabel yang sama
    protected $fillable = [
        'date',
        'description',
        'category',
        'cash_flow_category',
        'is_dividend',
    ];

    // Relasi ke detail
    public function details()
    {
        return $this->hasMany(TransactionDetail::class, 'transaction_id');
    }

    // Relasi ke akun
    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'transaction_details', 'transaction_id', 'account_id');
    }

    // Relasi ke UMKM
    public function umkm()
    {
        return $this->belongsTo(Umkm::class, 'umkm_id');
    }


// ✅ Accessor otomatis
protected $appends = ['nama_umkm'];

public function getNamaUmkmAttribute()
{
    return $this->umkm->nama_umkm ?? 'Tidak diketahui';
}

}
