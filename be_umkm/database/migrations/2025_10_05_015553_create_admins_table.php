<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique(); // 🔥 GANTI EMAIL → USERNAME
            $table->string('password');
            $table->enum('role', ['admin_dinas', 'dosen_uns', 'umkm'])->default('umkm');
            $table->timestamps();
        });

        // Insert akun default
        DB::table('admins')->insert([
            [
                'name' => 'Admin Dinas',
                'username' => 'admin_disperdakop', // ✅ username sesuai permintaan
                'password' => Hash::make('umkmdinas25'), // ✅ password sesuai permintaan
                'role' => 'admin_dinas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dosen UNS',
                'username' => 'dosen_uns',
                'password' => Hash::make('umkmdosen25'),
                'role' => 'dosen_uns',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};

