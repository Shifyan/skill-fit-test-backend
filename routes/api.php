<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HouseController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ResidentController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/houses', [HouseController::class, 'index']);
    Route::post('/houses', [HouseController::class, 'store']);
    Route::get('/houses/{id}', [HouseController::class, 'show']);
    Route::put('/houses/{id}', [HouseController::class, 'update']);
    Route::delete('/houses/{id}', [HouseController::class, 'destroy']);
    Route::post('/houses/{id}/assign', [HouseController::class, 'assign']);
    Route::post('/houses/{id}/unassign', [HouseController::class, 'unassign']);

    Route::get('/residents', [ResidentController::class, 'index']);
    Route::post('/residents', [ResidentController::class, 'store']);
    Route::get('/residents/{id}', [ResidentController::class, 'show']);
    Route::put('/residents/{id}', [ResidentController::class, 'update']);
    Route::post('/residents/{id}/update', [ResidentController::class, 'update']);
    Route::delete('/residents/{id}', [ResidentController::class, 'destroy']);

    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices/generate', [InvoiceController::class, 'generate']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::post('/payments', [InvoiceController::class, 'pay']);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

    Route::get('/reports/summary', [ReportController::class, 'summary']);
});
