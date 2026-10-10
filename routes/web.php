<?php

use App\Http\Controllers\BukuController;
use App\Http\Controllers\KategoriController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/buku');

Route::resource('buku', BukuController::class);
Route::resource('kategori', KategoriController::class);
