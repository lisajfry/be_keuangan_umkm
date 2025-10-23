<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UmkmAuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AccountController;

/*
|--------------------------------------------------------------------------
| AUTH UMKM
|--------------------------------------------------------------------------
*/
Route::post('/umkm/register', [UmkmAuthController::class, 'register']);
Route::post('/umkm/login', [UmkmAuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| ROUTE UMKM (Hanya untuk user UMKM yang login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:umkm')->group(function () {

    Route::post('/umkm/logout', [UmkmAuthController::class, 'logout']);

    // 🔹 Akun-akun UMKM (misal daftar rekening)
    Route::get('/accounts', [AccountController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | TRANSAKSI
    |--------------------------------------------------------------------------
    */
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);      // Transaksi UMKM ini
        Route::post('/', [TransactionController::class, 'store']);     // Tambah transaksi
        Route::get('/{id}', [TransactionController::class, 'show']);   // Detail transaksi
        Route::delete('/{id}', [TransactionController::class, 'destroy']); // Hapus transaksi
    });

    /*
    |--------------------------------------------------------------------------
    | LAPORAN KEUANGAN
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->group(function () {
        Route::get('/income-statement', [ReportController::class, 'incomeStatement']);
        Route::get('/retained-earnings', [ReportController::class, 'retainedEarnings']);
        Route::get('/balance-sheet', [ReportController::class, 'balanceSheet']);
        Route::get('/cash-flow', [ReportController::class, 'cashFlow']);
        Route::get('/summary', [ReportController::class, 'summary']);
        Route::get('/download-excel', [ReportController::class, 'downloadExcel']);
    });
});

/*
|--------------------------------------------------------------------------
| UNTUK BACKEND ADMIN (ambil semua transaksi UMKM)
|--------------------------------------------------------------------------
|
| Route ini dipakai oleh backend ADMIN (melalui proxy di AdminTransactionController)
| Jadi dia tidak pakai auth:umkm karena nanti akan diverifikasi oleh admin backend.
|
*/
Route::middleware('auth:sanctum')->get('/umkm/all/transactions', [TransactionController::class, 'adminIndex']);
