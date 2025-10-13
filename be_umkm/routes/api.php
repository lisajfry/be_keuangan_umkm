<?php
use App\Http\Controllers\UmkmAuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AccountController;


Route::post('/umkm/register', [UmkmAuthController::class, 'register']);
Route::post('/umkm/login', [UmkmAuthController::class, 'login']);

// routes yang butuh login UMKM
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/umkm/logout', [UmkmAuthController::class, 'logout']);
    Route::get('/accounts', [AccountController::class, 'index']);
 
    // transaksi
    Route::prefix('transactions')->group(function() {
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/', [TransactionController::class, 'store']);
        Route::get('/{id}', [TransactionController::class, 'show']);
        Route::delete('/{id}', [TransactionController::class, 'destroy']);
    });

    // laporan
    // laporan
Route::prefix('reports')->group(function() {
    Route::get('/income-statement', [ReportController::class, 'incomeStatement']);
    Route::get('/retained-earnings', [ReportController::class, 'retainedEarnings']);
    Route::get('/balance-sheet', [ReportController::class, 'balanceSheet']);
    Route::get('/cash-flow', [ReportController::class, 'cashFlow']);
    Route::get('/summary', [ReportController::class, 'summary']);
    Route::get('/download-excel', [ReportController::class, 'downloadExcel']);
});


});
