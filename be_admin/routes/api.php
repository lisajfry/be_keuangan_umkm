<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UmkmController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\UmkmApprovalController;
use App\Http\Controllers\AdminUmkmController; 
use App\Http\Controllers\TransactionController;

/*
|--------------------------------------------------------------------------
| AUTH ADMIN
|--------------------------------------------------------------------------
*/
Route::post('/admin/register', [AdminController::class, 'register']);
Route::post('/admin/login', [AdminController::class, 'login']);
Route::post('/admin/logout', [AdminController::class, 'logout'])->middleware('auth:admin');

/*
|--------------------------------------------------------------------------
| ROUTE ADMIN (Hanya untuk Admin login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:admin')->group(function () {

    // 🔹 CRUD UMKM
    Route::get('/umkms', [UmkmController::class, 'index']);
    Route::post('/umkms', [UmkmController::class, 'store']);
    Route::get('/umkms/{id}', [UmkmController::class, 'show']);
    Route::put('/umkms/{id}', [UmkmController::class, 'update']);
    Route::delete('/umkms/{id}', [UmkmController::class, 'destroy']);

    // 🔹 Validasi & Approval UMKM
    Route::get('/umkms/pending', [UmkmController::class, 'pending']);
    Route::post('/umkms/{id}/approve', [UmkmApprovalController::class, 'approve']);
    Route::post('/umkms/{id}/reject', [UmkmController::class, 'reject']);

    // 🔹 Transaksi
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/admin/transactions', [TransactionController::class, 'adminIndex']);
    Route::get('/umkm/{id}/transactions', [TransactionController::class, 'umkmTransactions']);

    /*
    |--------------------------------------------------------------------------
    | 🔹 Laporan Keuangan Admin (semua UMKM)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin/report')->group(function () {
        Route::get('/income-statement', [AdminReportController::class, 'incomeStatement']);
        Route::get('/balance-sheet', [AdminReportController::class, 'balanceSheet']);
        Route::get('/summary', [AdminReportController::class, 'summary']);
        Route::get('/download', [AdminReportController::class, 'downloadExcel']);

        // Tambahan:
        Route::get('/summary-all', [AdminReportController::class, 'summaryAllUmkm']);
        Route::get('/dashboard', [AdminReportController::class, 'dashboardSummary']);
    });
});
