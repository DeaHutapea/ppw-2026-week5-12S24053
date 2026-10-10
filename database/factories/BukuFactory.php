<?php

namespace Database\Factories;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Buku>
 */
class BukuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $judul = fake()->randomElement([
            'Pemrograman Web untuk Mahasiswa',
            'Dasar-Dasar Basis Data',
            'Sistem Informasi Manajemen',
            'Algoritma dan Struktur Data',
            'Jaringan Komputer Modern',
            'Rekayasa Perangkat Lunak',
            'Kecerdasan Buatan Dasar',
            'Analisis dan Desain Sistem',
            'Pengantar Teknologi Informasi',
            'Data Mining Praktis',
        ]);

        return [
            'isbn' => fake()->unique()->numerify('978##########'),
            'judul' => $judul,
            'penulis' => fake()->randomElement([
                'Rina Marlina',
                'Budi Santoso',
                'Dewi Anggraini',
                'Andi Wijaya',
                'Sari Rahmawati',
                'Fajar Nugroho',
                'Lina Hasanah',
                'Arif Pratama',
                'Maya Putri',
                'Dian Permana',
            ]),
            'penerbit' => fake()->randomElement([
                'Penerbit Informatika',
                'Deepublish',
                'Andi Offset',
                'Erlangga',
                'Media Press',
                'Bumi Aksara',
            ]),
            'tahun_terbit' => fake()->numberBetween(2018, now()->year),
            'kategori_id' => Kategori::factory(),
            'stok' => fake()->numberBetween(3, 30),
            'sinopsis' => fake()->randomElement([
                'Buku ini membahas konsep dasar dan praktik penerapan teknologi informasi dalam dunia akademik dan industri modern.',
                'Pembaca akan diajak memahami prinsip kerja sistem informasi serta cara menganalisis kebutuhan data secara efektif.',
                'Materi disusun secara berjenjang mulai dari pengenalan hingga implementasi nyata dalam proyek berbasis data dan aplikasi.',
                'Buku ini menyajikan pendekatan komprehensif tentang pengembangan perangkat lunak dan tata kelola data yang terstruktur.',
                'Setiap bab dilengkapi contoh studi kasus dan latihan yang relevan dengan kebutuhan pembelajaran mahasiswa dan praktisi.',
            ]),
        ];
    }
}
