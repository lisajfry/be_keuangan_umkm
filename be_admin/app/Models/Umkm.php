<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model;

class Umkm extends Model
{
    use HasFactory; 

    protected $fillable = [
        'admin_id', 'nama_umkm', 'alamat', 'nib', 'pirt',
        'no_hp', 'kategori_umkm', 'password', 'is_approved',
    ];

    protected $hidden = ['password'];

    protected $casts = [
    'is_approved' => 'boolean',
];


    // Relasi ke Admin
    public function admin()
{
    return $this->belongsTo(Admin::class, 'admin_id');
}


    // Relasi ke transaksi
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}

