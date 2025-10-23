<?php

return [

    'defaults' => [
        'guard' => 'admin', // 🔹 ubah default guard jadi admin
        'passwords' => 'admins', // 🔹 sesuaikan provider password
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Guard Sanctum umum (misal untuk UMKM)
        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],

        // 🔹 Guard khusus admin
        'admin' => [
            'driver' => 'sanctum', // penting untuk token-based auth
            'provider' => 'admins',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        // 🔹 Provider admin
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        // 🔹 tambahkan bagian ini buat admin
        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
