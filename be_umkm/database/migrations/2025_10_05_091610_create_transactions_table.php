<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('description')->nullable();
            // category bisa: penjualan, gaji, beli_peralatan, bayar_hutang, setor_modal, dividen
            $table->string('category')->nullable();
            // untuk klasifikasi cashflow (jika transaksi melibatkan kas)
            $table->enum('cash_flow_category', ['operating','investing','financing'])->nullable();
            // tanda kalau transaksi ini adalah pembagian dividen
            $table->boolean('is_dividend')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
