<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // ===== PUBLIK =====
    Route::post('/auth/login', [AuthTokenController::class, 'login'])->name('auth.login');

    // ===== TERPROTEKSI BEARER TOKEN (auth:sanctum) =====
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthTokenController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthTokenController::class, 'logout'])->name('auth.logout');
    });
});