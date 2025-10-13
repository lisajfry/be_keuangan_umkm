<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UmkmController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UmkmApprovalController;
use App\Http\Controllers\AdminUmkmController;

Route::post('/admin/register', [AdminController::class, 'register']);
Route::post('/admin/login', [AdminController::class, 'login']);
Route::post('/admin/logout', [AdminController::class, 'logout'])->middleware('auth:admin');

Route::middleware('auth:admin')->group(function () {
    // CRUD UMKM
    Route::get('/umkms', [UmkmController::class, 'index']);
    Route::post('/umkms', [UmkmController::class, 'store']);
    Route::get('/umkms/{id}', [UmkmController::class, 'show']);
    Route::put('/umkms/{id}', [UmkmController::class, 'update']);
    Route::delete('/umkms/{id}', [UmkmController::class, 'destroy']);

    // Validasi UMKM
    Route::get('/umkms/pending', [UmkmController::class, 'pending']);
    Route::post('/umkms/{id}/approve', [UmkmApprovalController::class, 'approve']);
    Route::post('/umkms/{id}/reject', [UmkmController::class, 'reject']);

    // Transaksi internal backend admin
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

    // Proxy transaksi ke backend UMKM
    Route::get('/umkm/all/transactions', [AdminUmkmController::class, 'proxyTransactions']);
    Route::get('/umkm/{umkm_id}/transactions', [AdminUmkmController::class, 'proxyTransactions']);
});
