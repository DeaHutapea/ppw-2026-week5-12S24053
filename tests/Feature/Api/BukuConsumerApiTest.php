<?php

namespace Tests\Feature\Api;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BukuConsumerApiTest extends TestCase
{
    use RefreshDatabase;

    private function buku(string $isbn = '9780132350884'): Buku
    {
        $kategori = Kategori::factory()->create();

        return Buku::factory()->create([
            'kategori_id' => $kategori->id,
            'isbn' => $isbn,
            'judul' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
        ]);
    }

    public function test_referensi_eksternal_returns_metadata_from_openlibrary(): void
    {
        $buku = $this->buku('9780132350884');
        $bibkey = 'ISBN:9780132350884';

        Http::fake([
            'https://openlibrary.org/api/books*' => Http::response([
                $bibkey => [
                    'title' => 'Clean Code',
                    'authors' => [
                        ['name' => 'Robert C. Martin'],
                    ],
                    'publishers' => [
                        ['name' => 'Prentice Hall'],
                    ],
                    'publish_date' => 'August 1, 2008',
                    'number_of_pages' => 464,
                    'url' => 'https://openlibrary.org/books/OL1121111M/Clean_Code',
                    'cover' => [
                        'small' => 'https://covers.openlibrary.org/b/id/111-S.jpg',
                        'medium' => 'https://covers.openlibrary.org/b/id/111-M.jpg',
                        'large' => 'https://covers.openlibrary.org/b/id/111-L.jpg',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson("/api/v1/bukus/{$buku->id}/referensi-eksternal");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.isbn', '9780132350884')
            ->assertJsonPath('data.sumber', 'OpenLibrary REST API')
            ->assertJsonPath('data.judul_eksternal', 'Clean Code')
            ->assertJsonPath('data.authors.0', 'Robert C. Martin')
            ->assertJsonPath('data.publishers.0', 'Prentice Hall')
            ->assertJsonPath('data.number_of_pages', 464);
    }

    public function test_referensi_eksternal_returns_404_when_local_book_not_found(): void
    {
        $response = $this->getJson('/api/v1/bukus/999999/referensi-eksternal');

        $response->assertStatus(404)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('title', 'Resource Not Found');
    }

    public function test_referensi_eksternal_returns_404_problem_when_not_found_in_openlibrary(): void
    {
        $buku = $this->buku('9780000000000');

        Http::fake([
            'https://openlibrary.org/api/books*' => Http::response([], 200),
        ]);

        $response = $this->getJson("/api/v1/bukus/{$buku->id}/referensi-eksternal");

        $response->assertStatus(404)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('title', 'External Metadata Not Found');

        $this->assertStringContainsString('tidak ditemukan', $response->json('detail'));
    }

    public function test_referensi_eksternal_returns_502_problem_on_upstream_failure(): void
    {
        $buku = $this->buku();

        Http::fake([
            'https://openlibrary.org/api/books*' => Http::response(['error' => 'server error'], 500),
        ]);

        $response = $this->getJson("/api/v1/bukus/{$buku->id}/referensi-eksternal");

        $response->assertStatus(502)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('title', 'External Service Unavailable');
    }

    public function test_referensi_eksternal_returns_504_problem_on_timeout(): void
    {
        $buku = $this->buku();

        Http::fake([
            'https://openlibrary.org/api/books*' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
        ]);

        $response = $this->getJson("/api/v1/bukus/{$buku->id}/referensi-eksternal");

        $response->assertStatus(504)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('title', 'External Service Timeout');
    }
}
