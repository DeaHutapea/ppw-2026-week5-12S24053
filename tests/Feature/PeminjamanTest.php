<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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

        $response = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('buku.index'))
            ->assertSessionHas('success', 'Sesi Anda telah aktif.')
            ->assertSessionHas('login_timestamp')
            ->assertCookie('del_last_user_email', $user->email);
        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === 'del_last_user_email');
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());

        $this->get(route('kategori.index'))->assertOk();

        $this->post(route('logout'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Sesi server telah dihapus dengan aman.');

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
            ->assertSessionHas('success')
            ->assertSessionHas('last_borrower', '12S24053 - Dea Hutapea');

        $peminjaman = Peminjaman::query()->firstOrFail();
        $this->assertSame(1, $buku->fresh()->stok);
        $this->assertSame('dipinjam', $peminjaman->status);
        $this->assertStringStartsWith('PINJAM-'.now()->format('Ymd').'-', $peminjaman->kode_pinjam);
        $this->assertLessThanOrEqual(30, strlen($peminjaman->kode_pinjam));

        $this->post(route('peminjaman.kembali', $peminjaman))
            ->assertRedirect(route('peminjaman.index'))
            ->assertSessionHas('success');

        $this->assertSame(2, $buku->fresh()->stok);
        $this->assertSame('dikembalikan', $peminjaman->fresh()->status);
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
            ->assertSessionHas('error', 'Stok buku tidak tersedia untuk dipinjam.');

        $this->assertDatabaseCount('peminjamans', 0);
        $this->assertSame(0, $buku->fresh()->stok);
    }

    public function test_same_student_cannot_borrow_the_same_book_twice_until_returned(): void
    {
        $this->actingAs(User::factory()->create());
        $kategori = Kategori::factory()->create();
        $buku = Buku::factory()->for($kategori)->create(['stok' => 2]);
        $payload = [
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24053',
            'nama_peminjam' => 'Dea Hutapea',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
        ];

        $this->post(route('peminjaman.store'), $payload)
            ->assertRedirect(route('peminjaman.index'));

        $peminjaman = Peminjaman::query()->firstOrFail();
        $this->from(route('peminjaman.create'))
            ->post(route('peminjaman.store'), [
                ...$payload,
                'nim_peminjam' => strtolower($payload['nim_peminjam']),
            ])
            ->assertRedirect(route('peminjaman.create'))
            ->assertSessionHas('error', 'NIM ini masih meminjam buku yang sama dan belum mengembalikannya.');

        $this->assertDatabaseCount('peminjamans', 1);
        $this->assertSame(1, $buku->fresh()->stok);

        $this->post(route('peminjaman.kembali', $peminjaman))
            ->assertRedirect(route('peminjaman.index'));
        $this->post(route('peminjaman.store'), $payload)
            ->assertRedirect(route('peminjaman.index'));

        $this->assertDatabaseCount('peminjamans', 2);
        $this->assertSame(1, $buku->fresh()->stok);
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
            ->assertDontSee($otherBook->judul)
            ->assertSessionHas('last_book_search', $matchingBook->judul);

        $this->get(route('buku.index', ['q' => "' OR 1=1 --"]))
            ->assertOk()
            ->assertDontSee($matchingBook->judul)
            ->assertDontSee($otherBook->judul);
    }

    public function test_catalog_filters_by_category_and_shows_borrowing_and_library_statistics(): void
    {
        $firstCategory = Kategori::factory()->create(['nama_kategori' => 'Kategori Pertama']);
        $secondCategory = Kategori::factory()->create(['nama_kategori' => 'Kategori Kedua']);
        $firstBook = Buku::factory()->for($firstCategory)->create([
            'judul' => 'Buku Kategori Pertama',
            'stok' => 4,
        ]);
        Buku::factory()->for($secondCategory)->create([
            'judul' => 'Buku Kategori Kedua',
            'stok' => 3,
        ]);

        Peminjaman::create([
            'kode_pinjam' => 'PINJAM-'.now()->format('Ymd').'-COUNT0000001',
            'buku_id' => $firstBook->id,
            'nim_peminjam' => '12S24054',
            'nama_peminjam' => 'Peminjam Aktif',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
            'status' => 'dipinjam',
        ]);
        Peminjaman::create([
            'kode_pinjam' => 'PINJAM-'.now()->format('Ymd').'-COUNT0000002',
            'buku_id' => $firstBook->id,
            'nim_peminjam' => '12S24055',
            'nama_peminjam' => 'Peminjam Selesai',
            'tanggal_pinjam' => now()->subDays(14)->toDateString(),
            'tanggal_jatuh_tempo' => now()->subDays(7)->toDateString(),
            'tanggal_kembali' => now()->subDays(8)->toDateString(),
            'status' => 'dikembalikan',
        ]);

        $response = $this->get(route('buku.index', ['kategori_id' => $firstCategory->id]));

        $response->assertOk()
            ->assertSee($firstBook->judul)
            ->assertDontSee('Buku Kategori Kedua')
            ->assertSee('Total Judul Buku')
            ->assertSee('Total Stok Tersedia')
            ->assertSee('Sedang Dipinjam')
            ->assertSee('Total Dikembalikan')
            ->assertSee('<td>2</td>', false);
        $response->assertViewHas('stats', [
            'total_judul' => 2,
            'total_stok' => 7,
            'total_dipinjam' => 1,
            'total_dikembalikan' => 1,
        ]);
    }

}
