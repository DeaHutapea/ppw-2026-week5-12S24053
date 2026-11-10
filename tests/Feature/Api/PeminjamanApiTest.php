<?php

namespace Tests\Feature\Api;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PeminjamanApiTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    private function buku(int $stok): Buku
    {
        return Buku::factory()->for(Kategori::factory()->create())->create(['stok' => $stok]);
    }

    private function body(Buku $buku, array $override = []): array
    {
        return array_merge([
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24099',
            'nama_peminjam' => 'Siti Mahasiswa',
            'durasi_hari' => 7,
        ], $override);
    }

    private function seedLoan(Buku $buku, string $nim, string $status, string $kode): Peminjaman
    {
        return Peminjaman::create([
            'kode_pinjam' => $kode,
            'buku_id' => $buku->id,
            'nim_peminjam' => $nim,
            'nama_peminjam' => 'Mahasiswa Uji',
            'tanggal_pinjam' => now()->subDays(2)->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(5)->toDateString(),
            'tanggal_kembali' => $status === 'dikembalikan' ? now()->toDateString() : null,
            'status' => $status,
        ]);
    }

    public function test_all_loan_endpoints_require_bearer_token(): void
    {
        $this->getJson('/api/v1/peminjamans')->assertStatus(401);
        $this->postJson('/api/v1/peminjamans', [])->assertStatus(401);
        $this->getJson('/api/v1/peminjamans/1')->assertStatus(401);
        $this->patchJson('/api/v1/peminjamans/1/kembalikan')->assertStatus(401)
            ->assertJsonPath('title', 'Unauthorized');
    }

    public function test_store_borrows_book_decrements_stock_and_returns_201_with_location(): void
    {
        $this->login();
        $buku = $this->buku(3);

        $res = $this->postJson('/api/v1/peminjamans', $this->body($buku, ['nim_peminjam' => ' 12s24099 ']))
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'dipinjam')
            ->assertJsonPath('data.nim_peminjam', '12S24099')
            ->assertJsonPath('data.tanggal_jatuh_tempo', now()->addDays(7)->toDateString())
            ->assertJsonPath('data._links.kembalikan_buku.method', 'PATCH');

        $id = $res->json('data.id');
        $res->assertHeader('Location', url("/api/v1/peminjamans/{$id}"));

        $this->assertSame(2, $buku->fresh()->stok);
        $this->assertDatabaseHas('peminjamans', ['id' => $id, 'buku_id' => $buku->id, 'status' => 'dipinjam']);
    }

    public function test_store_rejects_out_of_stock_with_422_and_changes_nothing(): void
    {
        $this->login();
        $buku = $this->buku(0);

        $this->postJson('/api/v1/peminjamans', $this->body($buku))
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('detail', 'Stok buku sedang habis.');

        $this->assertSame(0, $buku->fresh()->stok);
        $this->assertDatabaseCount('peminjamans', 0);
    }

    public function test_store_rejects_duplicate_active_loan_with_409(): void
    {
        $this->login();
        $buku = $this->buku(5);

        $this->postJson('/api/v1/peminjamans', $this->body($buku))->assertStatus(201);
        $this->postJson('/api/v1/peminjamans', $this->body($buku))
            ->assertStatus(409)
            ->assertJsonPath('status', 409);

        $this->assertSame(4, $buku->fresh()->stok); // hanya berkurang sekali
        $this->assertDatabaseCount('peminjamans', 1);
    }

    public function test_store_validation_returns_422_with_field_errors(): void
    {
        $this->login();
        $buku = $this->buku(2);

        $this->postJson('/api/v1/peminjamans', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['buku_id', 'nim_peminjam', 'nama_peminjam', 'durasi_hari']]);

        $this->postJson('/api/v1/peminjamans', $this->body($buku, ['durasi_hari' => 31, 'buku_id' => 999999]))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['buku_id', 'durasi_hari']]);
    }

    public function test_kembalikan_returns_book_increments_stock_and_hides_return_link(): void
    {
        $this->login();
        $buku = $this->buku(1);

        $id = $this->postJson('/api/v1/peminjamans', $this->body($buku))->json('data.id');
        $this->assertSame(0, $buku->fresh()->stok);

        $this->patchJson("/api/v1/peminjamans/{$id}/kembalikan")
            ->assertOk()
            ->assertJsonPath('data.status', 'dikembalikan')
            ->assertJsonPath('data.tanggal_kembali', now()->toDateString())
            ->assertJsonMissingPath('data._links.kembalikan_buku');

        $this->assertSame(1, $buku->fresh()->stok);
    }

    public function test_kembalikan_twice_returns_409_and_stock_is_not_added_again(): void
    {
        $this->login();
        $buku = $this->buku(2);

        $id = $this->postJson('/api/v1/peminjamans', $this->body($buku))->json('data.id');
        $this->patchJson("/api/v1/peminjamans/{$id}/kembalikan")->assertOk();

        $this->patchJson("/api/v1/peminjamans/{$id}/kembalikan")
            ->assertStatus(409)
            ->assertJsonPath('title', 'Loan Already Returned');

        $this->assertSame(2, $buku->fresh()->stok);
    }

    public function test_kembalikan_unknown_loan_returns_404(): void
    {
        $this->login();

        $this->patchJson('/api/v1/peminjamans/999999/kembalikan')
            ->assertStatus(404)
            ->assertJsonPath('status', 404);
    }

    public function test_same_student_can_borrow_again_after_returning(): void
    {
        $this->login();
        $buku = $this->buku(3);

        $id = $this->postJson('/api/v1/peminjamans', $this->body($buku))->json('data.id');
        $this->patchJson("/api/v1/peminjamans/{$id}/kembalikan")->assertOk();

        $this->postJson('/api/v1/peminjamans', $this->body($buku))->assertStatus(201);
    }

    public function test_index_filters_by_status_and_nim_with_pagination_meta(): void
    {
        $this->login();
        $buku = $this->buku(5);

        $this->seedLoan($buku, '12S24001', 'dipinjam', 'PINJAM-T-0001');
        $this->seedLoan($buku, '12S24001', 'dikembalikan', 'PINJAM-T-0002');
        $this->seedLoan($buku, '12S24002', 'dipinjam', 'PINJAM-T-0003');

        $this->getJson('/api/v1/peminjamans?per_page=2')
            ->assertOk()
            ->assertJsonPath('meta.total_items', 3)
            ->assertJsonPath('meta.total_pages', 2)
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/v1/peminjamans?status=dipinjam')
            ->assertOk()->assertJsonPath('meta.total_items', 2);

        $this->getJson('/api/v1/peminjamans?nim=12s24001&status=dikembalikan')
            ->assertOk()
            ->assertJsonPath('meta.total_items', 1)
            ->assertJsonPath('data.0.kode_pinjam', 'PINJAM-T-0002');

        $this->getJson('/api/v1/peminjamans?status=hilang')->assertStatus(422);
    }
}