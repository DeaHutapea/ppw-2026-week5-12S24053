<?php

namespace Tests\Feature\Api;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BukuReadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_public_and_returns_paginated_envelope_with_links(): void
    {
        $kategori = Kategori::factory()->create();
        Buku::factory()->count(3)->for($kategori)->create();

        $this->getJson('/api/v1/bukus?per_page=2')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total_items', 3)
            ->assertJsonPath('meta.total_pages', 2)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'isbn', 'judul', 'stok', 'kategori' => ['id', 'nama_kategori'], 'total_peminjaman', '_links' => ['self']]],
                'meta' => ['current_page', 'links' => ['first', 'prev', 'next', 'last']],
            ]);
    }

    public function test_index_avoids_n_plus_one_queries(): void
    {
        $kategori = Kategori::factory()->create();
        Buku::factory()->count(12)->for($kategori)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson('/api/v1/bukus?per_page=12')->assertOk();

        $this->assertLessThanOrEqual(5, count(DB::getQueryLog()));
    }

    public function test_index_supports_search_category_filter_availability_and_sorting(): void
    {
        $k1 = Kategori::factory()->create();
        $k2 = Kategori::factory()->create();

        Buku::factory()->for($k1)->create(['judul' => 'Algoritma Unik Zeta', 'stok' => 5]);
        Buku::factory()->for($k1)->create(['judul' => 'Buku Habis Stok', 'stok' => 0]);
        Buku::factory()->for($k2)->create(['judul' => 'Buku Kategori Lain', 'stok' => 9]);

        $this->getJson('/api/v1/bukus?search=Zeta')
            ->assertOk()->assertJsonPath('meta.total_items', 1);

        $this->getJson('/api/v1/bukus?kategori_id='.$k2->id)
            ->assertOk()->assertJsonPath('meta.total_items', 1);

        $res = $this->getJson('/api/v1/bukus?tersedia=1')->assertOk();
        $this->assertTrue(collect($res->json('data'))->every(fn ($b) => $b['stok'] > 0));
        $res->assertJsonPath('meta.total_items', 2);

        $this->getJson('/api/v1/bukus?sort=stok&dir=desc')
            ->assertOk()->assertJsonPath('data.0.stok', 9);
    }

    public function test_index_rejects_invalid_sort_with_422_problem_details(): void
    {
        $this->getJson('/api/v1/bukus?sort=hacker')
            ->assertStatus(422)
            ->assertJsonStructure(['type', 'title', 'status', 'detail', 'instance', 'errors' => ['sort']]);
    }

    public function test_show_returns_detail_with_category_history_and_pinjam_link_when_stock_available(): void
    {
        $buku = Buku::factory()->for(Kategori::factory()->create())->create(['stok' => 3]);

        Peminjaman::create([
            'kode_pinjam' => 'PINJAM-TEST-0001',
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24001',
            'nama_peminjam' => 'Mahasiswa Uji',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
            'status' => 'dipinjam',
        ]);

        $this->getJson("/api/v1/bukus/{$buku->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $buku->id)
            ->assertJsonPath('data.status_ketersediaan', 'TERSEDIA')
            ->assertJsonPath('data.total_peminjaman', 1)
            ->assertJsonPath('data.riwayat_peminjaman.0.nim_peminjam', '12S24001')
            ->assertJsonPath('data._links.pinjam_buku.method', 'POST')
            ->assertJsonStructure(['data' => ['kategori' => ['id', 'kode_kategori', 'nama_kategori']]]);
    }

    public function test_show_hides_pinjam_link_when_stock_is_empty(): void
    {
        $buku = Buku::factory()->for(Kategori::factory()->create())->create(['stok' => 0]);

        $this->getJson("/api/v1/bukus/{$buku->id}")
            ->assertOk()
            ->assertJsonPath('data.status_ketersediaan', 'HABIS')
            ->assertJsonMissingPath('data._links.pinjam_buku')
            ->assertJsonPath('data._links.self.method', 'GET');
    }

    public function test_show_returns_404_problem_details_when_book_not_found(): void
    {
        $this->getJson('/api/v1/bukus/999999')
            ->assertStatus(404)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 404)
            ->assertJsonPath('title', 'Resource Not Found');
    }
}