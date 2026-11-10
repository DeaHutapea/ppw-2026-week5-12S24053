<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\BukuApiController;
use App\Http\Controllers\Api\V1\PeminjamanApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // ===== PUBLIK =====
    Route::post('/auth/login', [AuthTokenController::class, 'login'])->name('auth.login');
    Route::get('/bukus', [BukuApiController::class, 'index'])->name('bukus.index');
    Route::get('/bukus/{id}', [BukuApiController::class, 'show'])->whereNumber('id')->name('bukus.show');
    Route::get('/bukus/{id}/referensi-eksternal', [BukuApiController::class, 'referensiEksternal'])->whereNumber('id')->name('bukus.referensi');

    // ===== TERPROTEKSI BEARER TOKEN (auth:sanctum) =====
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthTokenController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthTokenController::class, 'logout'])->name('auth.logout');

        // Mutasi katalog buku
        Route::post('/bukus', [BukuApiController::class, 'store'])->name('bukus.store');
        Route::put('/bukus/{id}', [BukuApiController::class, 'update'])->whereNumber('id')->name('bukus.replace');
        Route::patch('/bukus/{id}', [BukuApiController::class, 'update'])->whereNumber('id')->name('bukus.update');
        Route::delete('/bukus/{id}', [BukuApiController::class, 'destroy'])->whereNumber('id')->name('bukus.destroy');

        // Transaksi sirkulasi peminjaman
        Route::get('/peminjamans', [PeminjamanApiController::class, 'index'])->name('peminjamans.index');
        Route::post('/peminjamans', [PeminjamanApiController::class, 'store'])->name('peminjamans.store');
        Route::get('/peminjamans/{id}', [PeminjamanApiController::class, 'show'])->whereNumber('id')->name('peminjamans.show');
        Route::patch('/peminjamans/{id}/kembalikan', [PeminjamanApiController::class, 'kembalikan'])->whereNumber('id')->name('peminjamans.kembalikan');
    });
});