<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PeminjamanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_staff_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@del.ac.id',
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect(route('buku.index'));

        $this->get(route('kategori.index'))->assertOk();

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->get(route('kategori.index'))->assertRedirect(route('login'));
    }

    public function test_guest_can_browse_books_but_cannot_manage_library_data(): void
    {
        $kategori = Kategori::factory()->create();
        $buku = Buku::factory()->for($kategori)->create();

        $this->get(route('buku.index'))->assertOk()->assertSee($buku->judul);
        $this->get(route('buku.create'))->assertRedirect(route('login'));
        $this->get(route('kategori.index'))->assertRedirect(route('login'));
        $this->get(route('peminjaman.index'))->assertRedirect(route('login'));
    }

    public function test_staff_can_borrow_and_return_a_book_with_stock_updated_once(): void
    {
        $this->actingAs(User::factory()->create());
        $kategori = Kategori::factory()->create();
        $buku = Buku::factory()->for($kategori)->create(['stok' => 2]);
        $tanggalPinjam = now()->toDateString();

        $this->post(route('peminjaman.store'), [
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24053',
            'nama_peminjam' => 'Dea Hutapea',
            'tanggal_pinjam' => $tanggalPinjam,
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
        ])->assertRedirect(route('peminjaman.index'))
            ->assertSessionHas('success');

        $peminjaman = Peminjaman::query()->firstOrFail();
        $this->assertSame(1, $buku->fresh()->stok);
        $this->assertSame('dipinjam', $peminjaman->status);
        $this->assertStringStartsWith('PINJAM-'.now()->format('Ymd').'-', $peminjaman->kode_pinjam);
        $this->assertLessThanOrEqual(30, strlen($peminjaman->kode_pinjam));

        $this->post(route('peminjaman.kembali', $peminjaman))
            ->assertRedirect(route('peminjaman.index'))
            ->assertSessionHas('success');

        $this->assertSame(2, $buku->fresh()->stok);
        $this->assertSame('kembali', $peminjaman->fresh()->status);
        $this->assertNotNull($peminjaman->fresh()->tanggal_kembali);

        $this->from(route('peminjaman.index'))
            ->post(route('peminjaman.kembali', $peminjaman))
            ->assertRedirect(route('peminjaman.index'))
            ->assertSessionHas('error');

        $this->assertSame(2, $buku->fresh()->stok);
    }

    public function test_borrowing_is_rejected_when_book_has_no_stock(): void
    {
        $this->actingAs(User::factory()->create());
        $kategori = Kategori::factory()->create();
        $buku = Buku::factory()->for($kategori)->create(['stok' => 0]);

        $this->from(route('peminjaman.create'))
            ->post(route('peminjaman.store'), [
                'buku_id' => $buku->id,
                'nim_peminjam' => '12S24053',
                'nama_peminjam' => 'Dea Hutapea',
                'tanggal_pinjam' => now()->toDateString(),
                'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
            ])
            ->assertRedirect(route('peminjaman.create'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('peminjamans', 0);
        $this->assertSame(0, $buku->fresh()->stok);
    }

    public function test_book_with_borrowing_history_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $kategori = Kategori::factory()->create();
        $buku = Buku::factory()->for($kategori)->create(['stok' => 0]);
        Peminjaman::create([
            'kode_pinjam' => 'PINJAM-'.now()->format('Ymd').'-ABC123DEF456',
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24053',
            'nama_peminjam' => 'Dea Hutapea',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
            'status' => 'dipinjam',
        ]);

        $this->delete(route('buku.destroy', $buku))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('bukus', ['id' => $buku->id]);
        $this->assertDatabaseCount('peminjamans', 1);
    }

    public function test_book_search_uses_bound_query_values_and_returns_matching_books(): void
    {
        $kategori = Kategori::factory()->create();
        $matchingBook = Buku::factory()->for($kategori)->create([
            'judul' => 'Laravel Aman',
        ]);
        $otherBook = Buku::factory()->for($kategori)->create([
            'judul' => 'Basis Data Lanjut',
        ]);

        $this->get(route('buku.index', ['q' => $matchingBook->judul]))
            ->assertOk()
            ->assertSee($matchingBook->judul)
            ->assertDontSee($otherBook->judul);

        $this->get(route('buku.index', ['q' => "' OR 1=1 --"]))
            ->assertOk()
            ->assertDontSee($matchingBook->judul)
            ->assertDontSee($otherBook->judul);
    }
}
