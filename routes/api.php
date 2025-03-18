<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;


/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

*/
// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/activateAccount', [AuthController::class, 'activateAccount']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forget-password', [AuthController::class, 'forgetPassword']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/verify-transaction', [TransactionController::class, 'verifyTransaction']);
    //Route::middleware(['web'])->post('verifyTransaction', [TransactionController::class, 'verifyTransaction']);

    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/transfer', [TransactionController::class, 'transferMoney']);
    Route::get('/transactions', [TransactionController::class, 'getUserTransactions']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

});
