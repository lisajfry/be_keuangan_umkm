<?php

return [


    

    'defaults' => [
        'guard' => 'umkm', // 🔹 default guard langsung ke umkm
        'passwords' => 'umkms',
    ],

    'guards' => [
        'umkm' => [
            'driver' => 'sanctum', // 🔹 pakai sanctum
            'provider' => 'umkms',
        ],
    ],

    'providers' => [
        'umkms' => [
            'driver' => 'eloquent',
            'model' => App\Models\Umkm::class, // 🔹 model autentikasi
        ],
    ],

    'passwords' => [
        'umkms' => [
            'provider' => 'umkms',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
