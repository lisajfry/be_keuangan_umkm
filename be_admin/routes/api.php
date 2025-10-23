<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UmkmController;

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

   

    Route::get('/transactions', [TransactionController::class, 'index']);

    // Untuk admin (lihat semua UMKM)
    Route::get('/admin/transactions', [TransactionController::class, 'adminIndex']);

    // Jika frontend memang minta by /umkm/{id}/transactions
    Route::get('/umkm/{id}/transactions', [TransactionController::class, 'umkmTransactions']);
});
