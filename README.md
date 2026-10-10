# SIPUS-Del

Sistem Informasi Perpustakaan Kampus Del berbasis Laravel 11 dengan pola MVC. Aplikasi ini mengelola koleksi buku dan kategori melalui CRUD RESTful, relasi Eloquent, validasi server-side, serta tampilan Blade bertema Del Purple.

## Kebutuhan

- PHP 8.2 atau lebih baru dengan ekstensi PDO SQLite
- Composer 2.6 atau lebih baru
- Node.js 18/20 dan npm

## Menjalankan aplikasi

1. Salin `.env.example` menjadi `.env`, lalu sesuaikan pengaturan lokal bila diperlukan. Jangan commit `.env`.
2. Pasang dependensi dan buat kunci aplikasi:

   ```bash
   composer install
   php artisan key:generate
   ```

3. Siapkan skema serta data contoh:

   ```bash
   php artisan migrate:fresh --seed
   ```

   Seeder membuat 5 kategori dan 20 buku contoh.

4. Pasang dependensi frontend dan kompilasi aset Vite:

   ```bash
   npm install
   npm run build
   ```

5. Jalankan server lokal:

   ```bash
   php artisan serve
   ```

   Buka URL yang ditampilkan Artisan. Halaman `/` akan mengarahkan ke katalog buku.

## Fitur

- CRUD buku dengan ISBN unik 13 digit, kategori, stok, sinopsis, dan validasi input server-side.
- CRUD kategori; kategori yang masih memiliki buku tidak dapat dihapus agar data buku tetap utuh.
- Relasi `Kategori::bukus()` dan `Buku::kategori()` dengan proteksi mass assignment melalui `$fillable`.
- Katalog menggunakan `Buku::with('kategori')` dan simple pagination untuk menghindari N+1 sekaligus membatasi kueri daftar menjadi satu kueri buku dan satu kueri kategori.
- Seluruh form memakai CSRF, form edit memakai method spoofing, dan output Blade di-escape secara default.
- Layout Blade `<x-layout>` dengan notifikasi flash, tampilan kosong `@forelse`, dan warna Del Purple `#4C1D95`.

## Pengujian

Jalankan pengujian fitur:

```bash
php artisan test
```

Tes katalog mengaudit kueri yang berjalan dan memastikan hanya ada satu kueri untuk buku serta satu kueri eager load kategori. Saat aplikasi berjalan dengan `APP_ENV=local`, listener pada `AppServiceProvider` mencatat SQL dan waktu eksekusi ke `storage/logs/laravel.log`. Buka katalog dan periksa log tersebut untuk melihat bukti eager loading.

## Skema utama

- `kategoris`: `kode_kategori` unik (maks. 10 karakter) dan `nama_kategori` (maks. 100 karakter).
- `bukus`: ISBN unik 13 digit, judul, penulis, penerbit, tahun terbit, foreign key kategori, stok non-negatif, dan sinopsis opsional.
- Foreign key buku ke kategori membatasi penghapusan kategori yang masih digunakan.
