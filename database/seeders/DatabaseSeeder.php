<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $kategoris = collect([
            ['kode_kategori' => 'KT-001', 'nama_kategori' => 'Teknologi Informasi'],
            ['kode_kategori' => 'KT-002', 'nama_kategori' => 'Ilmu Komputer'],
            ['kode_kategori' => 'KT-003', 'nama_kategori' => 'Sains dan Teknologi'],
            ['kode_kategori' => 'KT-004', 'nama_kategori' => 'Sastra dan Humaniora'],
            ['kode_kategori' => 'KT-005', 'nama_kategori' => 'Referensi'],
        ])->map(fn (array $kategori): Kategori => Kategori::create($kategori));

        $kategoris->each(function (Kategori $kategori): void {
            Buku::factory()->count(4)->for($kategori)->create();
        });
    }
}
