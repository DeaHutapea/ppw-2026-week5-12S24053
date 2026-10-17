<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Pustakawan SIPUS',
            'email' => 'pustakawan@del.ac.id',
            'password' => Hash::make('password123'),
        ]);

        $kategoris = collect([
            ['kode_kategori' => 'KT-001', 'nama_kategori' => 'Teknologi Informasi'],
            ['kode_kategori' => 'KT-002', 'nama_kategori' => 'Ilmu Komputer'],
            ['kode_kategori' => 'KT-003', 'nama_kategori' => 'Sains dan Teknologi'],
            ['kode_kategori' => 'KT-004', 'nama_kategori' => 'Sastra dan Humaniora'],
            ['kode_kategori' => 'KT-005', 'nama_kategori' => 'Referensi'],
        ])->map(fn (array $kategori): Kategori => Kategori::create($kategori));

        $bukus = $kategoris->flatMap(
            fn (Kategori $kategori) => Buku::factory()->count(4)->for($kategori)->create()
        );
        $bukuTanpaStok = $bukus->get(0);
        $bukuSatuStok = $bukus->get(1);

        $bukuTanpaStok->update([
            'isbn' => '9780000000000',
            'judul' => 'Buku Uji Stok Nol',
            'stok' => 0,
        ]);
        $bukuSatuStok->update([
            'isbn' => '9780000000017',
            'judul' => 'Buku Uji Stok Satu',
            'stok' => 1,
        ]);

        Peminjaman::create([
            'kode_pinjam' => 'PINJAM-SEED-0001',
            'buku_id' => $bukuTanpaStok->id,
            'nim_peminjam' => '12S24001',
            'nama_peminjam' => 'Mahasiswa Peminjam Aktif',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
            'status' => 'dipinjam',
        ]);

        Peminjaman::create([
            'kode_pinjam' => 'PINJAM-SEED-0002',
            'buku_id' => $bukuSatuStok->id,
            'nim_peminjam' => '12S24002',
            'nama_peminjam' => 'Mahasiswa Peminjam Selesai',
            'tanggal_pinjam' => now()->subDays(14)->toDateString(),
            'tanggal_jatuh_tempo' => now()->subDays(7)->toDateString(),
            'tanggal_kembali' => now()->subDays(8)->toDateString(),
            'status' => 'dikembalikan',
        ]);
    }
}
