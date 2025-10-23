<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // untuk autentikasi
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // penting untuk sanctum

class Umkm extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'umkms';

    protected $fillable = [
        'admin_id',
        'nama_umkm',
        'alamat',
        'nib',
        'pirt',
        'no_hp',
        'kategori_umkm',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function transactions()
{
    return $this->hasMany(Transaction::class, 'umkm_id');
}


    
}
