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
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin_dinas', 'dosen_uns', 'umkm'])->default('umkm');
            $table->timestamps();
        });

        // Insert default accounts untuk login awal
        DB::table('admins')->insert([
            [
                'name' => 'Admin Dinas',
                'email' => 'admin_dinas@example.com',
                'password' => Hash::make('password123'),
                'role' => 'admin_dinas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dosen UNS',
                'email' => 'dosen_uns@example.com',
                'password' => Hash::make('password123'),
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
