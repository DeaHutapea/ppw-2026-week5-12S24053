<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PeminjamanController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/buku');

Route::get('/buku', [BukuController::class, 'index'])->name('buku.index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::resource('buku', BukuController::class)->except(['index', 'show']);
    Route::resource('kategori', KategoriController::class);
    Route::resource('peminjaman', PeminjamanController::class)->only(['index', 'create', 'store']);
    Route::post('/peminjaman/{peminjaman}/kembali', [PeminjamanController::class, 'kembali'])
        ->name('peminjaman.kembali');
});

Route::get('/buku/{buku}', [BukuController::class, 'show'])->name('buku.show');
