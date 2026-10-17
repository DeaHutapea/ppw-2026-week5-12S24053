<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SipusCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->create());
    }

    public function test_book_catalog_eager_loads_categories_and_runs_aggregate_queries(): void
    {
        $kategori = Kategori::factory()->create();
        Buku::factory()->count(3)->for($kategori)->create();
        $queries = [];

        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->get(route('buku.index'))
            ->assertOk()
            ->assertSee($kategori->nama_kategori);

        $bookQueries = collect($queries)
            ->filter(fn (string $sql): bool => str_contains($sql, 'from "bukus"'));
        $catalogQueries = $bookQueries->filter(fn (string $sql): bool => str_contains($sql, 'peminjamans_count'));
        $categoryQueries = collect($queries)->filter(fn (string $sql): bool => str_contains($sql, 'from "kategoris"'));
        $categoryEagerLoads = $categoryQueries->filter(fn (string $sql): bool => str_contains($sql, ' where '));

        $this->assertCount(1, $catalogQueries);
        $this->assertCount(2, $categoryQueries);
        $this->assertCount(1, $categoryEagerLoads);
    }

    public function test_book_and_category_pages_render(): void
    {
        $kategori = Kategori::factory()->create();
        $buku = Buku::factory()->for($kategori)->create();

        $this->get(route('buku.create'))->assertOk();
        $this->get(route('buku.show', $buku))->assertOk()->assertSee($buku->judul);
        $this->get(route('buku.edit', $buku))->assertOk();
        $this->get(route('kategori.index'))->assertOk()->assertSee($kategori->nama_kategori);
        $this->get(route('kategori.create'))->assertOk();
        $this->get(route('kategori.show', $kategori))->assertOk()->assertSee($buku->judul);
        $this->get(route('kategori.edit', $kategori))->assertOk();
    }

    public function test_book_can_be_created_updated_and_deleted(): void
    {
        $kategori = Kategori::factory()->create();
        $bookData = [
            'isbn' => '9781234567890',
            'judul' => 'Belajar Sistem Informasi',
            'penulis' => 'Ayu Pratama',
            'penerbit' => 'Del Press',
            'tahun_terbit' => now()->year,
            'kategori_id' => $kategori->id,
            'stok' => 5,
            'sinopsis' => 'Buku pengantar sistem informasi.',
        ];

        $this->post(route('buku.store'), $bookData)
            ->assertRedirect(route('buku.index'))
            ->assertSessionHas('success');

        $buku = Buku::query()->firstOrFail();
        $this->assertDatabaseHas('bukus', ['isbn' => $bookData['isbn'], 'kategori_id' => $kategori->id]);

        $this->put(route('buku.update', $buku), [
            ...$bookData,
            'isbn' => '9781234567891',
            'judul' => 'Belajar Sistem Informasi Lanjut',
        ])->assertRedirect(route('buku.index'));

        $this->assertDatabaseHas('bukus', ['id' => $buku->id, 'isbn' => '9781234567891']);

        $this->delete(route('buku.destroy', $buku))
            ->assertRedirect(route('buku.index'));

        $this->assertDatabaseMissing('bukus', ['id' => $buku->id]);
    }

    public function test_book_validation_rejects_duplicate_isbn_short_title_and_negative_stock(): void
    {
        $kategori = Kategori::factory()->create();
        $existingBook = Buku::factory()->for($kategori)->create(['isbn' => '9781234567890']);

        $this->from(route('buku.create'))
            ->post(route('buku.store'), [
                'isbn' => $existingBook->isbn,
                'judul' => 'Buku',
                'penulis' => 'Ayu Pratama',
                'penerbit' => 'Del Press',
                'tahun_terbit' => now()->year,
                'kategori_id' => $kategori->id,
                'stok' => -1,
            ])
            ->assertRedirect(route('buku.create'))
            ->assertSessionHasErrors(['isbn', 'judul', 'stok']);
    }

    public function test_category_cannot_be_deleted_while_it_has_books(): void
    {
        $kategori = Kategori::factory()->create();
        Buku::factory()->for($kategori)->create();

        $this->delete(route('kategori.destroy', $kategori))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('kategoris', ['id' => $kategori->id]);
    }

    public function test_category_can_be_created_updated_and_deleted(): void
    {
        $this->post(route('kategori.store'), [
            'kode_kategori' => 'KT-NEW',
            'nama_kategori' => 'Buku Baru',
        ])->assertRedirect(route('kategori.index'));

        $kategori = Kategori::query()->where('kode_kategori', 'KT-NEW')->firstOrFail();

        $this->put(route('kategori.update', $kategori), [
            'kode_kategori' => 'KT-NEW',
            'nama_kategori' => 'Koleksi Baru',
        ])->assertRedirect(route('kategori.index'));

        $this->assertDatabaseHas('kategoris', [
            'id' => $kategori->id,
            'nama_kategori' => 'Koleksi Baru',
        ]);

        $this->delete(route('kategori.destroy', $kategori))
            ->assertRedirect(route('kategori.index'));

        $this->assertDatabaseMissing('kategoris', ['id' => $kategori->id]);
    }

    public function test_seed_data_contains_five_categories_and_twenty_books(): void
    {
        $this->seed();

        $this->assertDatabaseCount('kategoris', 5);
        $this->assertDatabaseCount('bukus', 20);
        $this->assertDatabaseHas('users', ['email' => 'pustakawan@del.ac.id']);
        $this->assertTrue(Hash::check(
            'password123',
            User::query()->where('email', 'pustakawan@del.ac.id')->value('password')
        ));
        $this->assertDatabaseHas('bukus', ['stok' => 0]);
        $this->assertDatabaseHas('bukus', ['stok' => 1]);
        $this->assertDatabaseHas('bukus', ['isbn' => '9780000000000', 'judul' => 'Buku Uji Stok Nol', 'stok' => 0]);
        $this->assertDatabaseHas('bukus', ['isbn' => '9780000000017', 'judul' => 'Buku Uji Stok Satu', 'stok' => 1]);
        $this->assertDatabaseHas('peminjamans', ['status' => 'dipinjam']);
        $this->assertDatabaseHas('peminjamans', ['status' => 'dikembalikan']);
        $this->assertDatabaseCount('peminjamans', 2);
        $this->assertDatabaseHas('peminjamans', [
            'kode_pinjam' => 'PINJAM-SEED-0001',
            'nim_peminjam' => '12S24001',
            'status' => 'dipinjam',
        ]);
        $this->assertDatabaseHas('peminjamans', [
            'kode_pinjam' => 'PINJAM-SEED-0002',
            'nim_peminjam' => '12S24002',
            'status' => 'dikembalikan',
        ]);
    }
}
