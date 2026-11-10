<?php

namespace Tests\Feature\Api;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BukuWriteApiTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    private function payload(int $kategoriId, array $override = []): array
    {
        return array_merge([
            'isbn' => '9786021234567',
            'judul' => 'Pemrograman Web Laravel',
            'penulis' => 'Dea Hutapea',
            'penerbit' => 'IT Del Press',
            'tahun_terbit' => 2024,
            'kategori_id' => $kategoriId,
            'stok' => 5,
            'sinopsis' => 'Buku latihan REST API.',
        ], $override);
    }

    private function pinjam(Buku $buku, string $status): void
    {
        Peminjaman::create([
            'kode_pinjam' => 'PINJAM-T-'.strtoupper(substr(md5($buku->id.$status), 0, 6)),
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24001',
            'nama_peminjam' => 'Mahasiswa Uji',
            'tanggal_pinjam' => now()->subDays(3)->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(4)->toDateString(),
            'tanggal_kembali' => $status === 'dikembalikan' ? now()->toDateString() : null,
            'status' => $status,
        ]);
    }

    public function test_write_endpoints_require_bearer_token(): void
    {
        $buku = Buku::factory()->for(Kategori::factory()->create())->create();

        $this->postJson('/api/v1/bukus', [])->assertStatus(401)->assertJsonPath('title', 'Unauthorized');
        $this->putJson("/api/v1/bukus/{$buku->id}", [])->assertStatus(401);
        $this->patchJson("/api/v1/bukus/{$buku->id}", [])->assertStatus(401);
        $this->deleteJson("/api/v1/bukus/{$buku->id}")->assertStatus(401);
    }

    public function test_store_creates_book_and_returns_201_with_location_header(): void
    {
        $this->login();
        $kategori = Kategori::factory()->create();

        $res = $this->postJson('/api/v1/bukus', $this->payload($kategori->id))
            ->assertStatus(201)
            ->assertJsonPath('data.isbn', '9786021234567')
            ->assertJsonPath('data.kategori.id', $kategori->id);

        $id = $res->json('data.id');
        $res->assertHeader('Location', url("/api/v1/bukus/{$id}"));
        $this->assertDatabaseHas('bukus', ['id' => $id, 'judul' => 'Pemrograman Web Laravel']);
    }

    public function test_store_validation_returns_422_with_field_errors(): void
    {
        $this->login();
        $kategori = Kategori::factory()->create();
        Buku::factory()->for($kategori)->create(['isbn' => '9786021234567']);

        $this->postJson('/api/v1/bukus', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['isbn', 'judul', 'penulis', 'penerbit', 'tahun_terbit', 'kategori_id', 'stok']]);

        $this->postJson('/api/v1/bukus', $this->payload(999999, [
            'judul' => 'abc',
            'stok' => -1,
        ]))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['isbn', 'judul', 'kategori_id', 'stok']]);
    }

    public function test_put_replaces_all_attributes_and_resets_omitted_nullable_field(): void
    {
        $this->login();
        $kategori = Kategori::factory()->create();
        $buku = Buku::factory()->for($kategori)->create(['sinopsis' => 'Sinopsis lama yang harus hilang.']);

        $body = $this->payload($kategori->id, ['judul' => 'Judul Hasil PUT Baru']);
        unset($body['sinopsis']);

        $this->putJson("/api/v1/bukus/{$buku->id}", $body)
            ->assertOk()
            ->assertJsonPath('data.judul', 'Judul Hasil PUT Baru')
            ->assertJsonPath('data.sinopsis', null);

        $this->assertNull($buku->fresh()->sinopsis);
    }

    public function test_put_requires_complete_representation(): void
    {
        $this->login();
        $buku = Buku::factory()->for(Kategori::factory()->create())->create();

        $this->putJson("/api/v1/bukus/{$buku->id}", ['stok' => 10])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['isbn', 'judul', 'penulis', 'penerbit', 'tahun_terbit', 'kategori_id']]);
    }

    public function test_patch_updates_only_sent_attributes(): void
    {
        $this->login();
        $buku = Buku::factory()->for(Kategori::factory()->create())->create([
            'judul' => 'Judul Tetap Aman',
            'sinopsis' => 'Sinopsis tetap utuh.',
            'stok' => 2,
        ]);

        $this->patchJson("/api/v1/bukus/{$buku->id}", ['stok' => 7])
            ->assertOk()
            ->assertJsonPath('data.stok', 7)
            ->assertJsonPath('data.judul', 'Judul Tetap Aman')
            ->assertJsonPath('data.sinopsis', 'Sinopsis tetap utuh.');
    }

    public function test_patch_with_empty_payload_returns_400(): void
    {
        $this->login();
        $buku = Buku::factory()->for(Kategori::factory()->create())->create();

        $this->patchJson("/api/v1/bukus/{$buku->id}", [])
            ->assertStatus(400)
            ->assertJsonPath('title', 'Bad Request');
    }

    public function test_update_and_delete_unknown_book_return_404(): void
    {
        $this->login();

        $this->patchJson('/api/v1/bukus/999999', ['stok' => 1])->assertStatus(404);
        $this->deleteJson('/api/v1/bukus/999999')->assertStatus(404)->assertJsonPath('status', 404);
    }

    public function test_destroy_rejects_with_409_when_active_loan_exists(): void
    {
        $this->login();
        $buku = Buku::factory()->for(Kategori::factory()->create())->create();
        $this->pinjam($buku, 'dipinjam');

        $this->deleteJson("/api/v1/bukus/{$buku->id}")
            ->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('title', 'Conflict: Book Has Active Loans');

        $this->assertDatabaseHas('bukus', ['id' => $buku->id]);
    }

    public function test_destroy_returns_204_when_no_active_loan(): void
    {
        $this->login();
        $buku = Buku::factory()->for(Kategori::factory()->create())->create();
        $this->pinjam($buku, 'dikembalikan');

        $this->deleteJson("/api/v1/bukus/{$buku->id}")->assertNoContent();

        $this->assertDatabaseMissing('bukus', ['id' => $buku->id]);
    }
}