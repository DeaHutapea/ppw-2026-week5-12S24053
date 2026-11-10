<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BukuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tersedia = $this->stok > 0;

        // HATEOAS (Richardson Level 3): tautan aksi bersifat kontekstual
        $links = [
            'self' => ['href' => url("/api/v1/bukus/{$this->id}"), 'method' => 'GET'],
            'collection' => ['href' => url('/api/v1/bukus'), 'method' => 'GET'],
            'update_full' => ['href' => url("/api/v1/bukus/{$this->id}"), 'method' => 'PUT'],
            'update_partial' => ['href' => url("/api/v1/bukus/{$this->id}"), 'method' => 'PATCH'],
            'delete' => ['href' => url("/api/v1/bukus/{$this->id}"), 'method' => 'DELETE'],
            'referensi_eksternal' => ['href' => url("/api/v1/bukus/{$this->id}/referensi-eksternal"), 'method' => 'GET'],
        ];

        // Link "pinjam_buku" HANYA muncul jika stok masih tersedia
        if ($tersedia) {
            $links['pinjam_buku'] = ['href' => url('/api/v1/peminjamans'), 'method' => 'POST'];
        }

        return [
            'id' => $this->id,
            'isbn' => $this->isbn,
            'judul' => $this->judul,
            'penulis' => $this->penulis,
            'penerbit' => $this->penerbit,
            'tahun_terbit' => (int) $this->tahun_terbit,
            'stok' => (int) $this->stok,
            'status_ketersediaan' => $tersedia ? 'TERSEDIA' : 'HABIS',
            'sinopsis' => $this->sinopsis,
            'total_peminjaman' => $this->whenCounted('peminjamans'),
            'kategori' => $this->whenLoaded('kategori', fn () => [
                'id' => $this->kategori->id,
                'kode_kategori' => $this->kategori->kode_kategori,
                'nama_kategori' => $this->kategori->nama_kategori,
            ]),
            'riwayat_peminjaman' => $this->whenLoaded('peminjamans', fn () => $this->peminjamans->map(fn ($p) => [
                'id' => $p->id,
                'kode_pinjam' => $p->kode_pinjam,
                'nim_peminjam' => $p->nim_peminjam,
                'nama_peminjam' => $p->nama_peminjam,
                'tanggal_pinjam' => $p->tanggal_pinjam?->toDateString(),
                'tanggal_jatuh_tempo' => $p->tanggal_jatuh_tempo?->toDateString(),
                'tanggal_kembali' => $p->tanggal_kembali?->toDateString(),
                'status' => $p->status,
            ])->values()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            '_links' => $links,
        ];
    }
}