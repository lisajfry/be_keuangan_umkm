<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable(); // opsional (101, 201)
            $table->string('name'); // Kas, Piutang, Peralatan, Perlengkapan, Utang, Modal
            $table->enum('type', ['asset','liability','equity','revenue','expense']);
            $table->boolean('is_cash')->default(false); // tandai akun kas untuk cash flow
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
