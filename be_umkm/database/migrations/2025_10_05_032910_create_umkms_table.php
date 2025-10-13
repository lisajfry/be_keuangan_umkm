<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('umkms', function (Blueprint $table) {
            $table->id();
            
            // Foreign key ke admins
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();

            $table->string('nama_umkm');
            $table->text('alamat')->nullable();
            $table->string('nib')->unique(); // unik, untuk login
            $table->string('pirt')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('kategori_umkm')->nullable();
            $table->string('password')->nullable(); // password login
            $table->boolean('is_approved')->default(false); // false = belum divalidasi admin
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkms');
    }
};
