<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PeminjamanResource;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PeminjamanApiController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/peminjamans (Bearer Token)
     * Query: status=dipinjam|dikembalikan, nim, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $v = $request->validate([
            'status' => ['nullable', Rule::in(['dipinjam', 'dikembalikan'])],
            'nim' => ['nullable', 'string', 'max:15'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $paginator = Peminjaman::query()
            ->with('buku') // Bebas N+1
            ->when($v['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($v['nim'] ?? null, fn ($q, $nim) => $q->where('nim_peminjam', Str::upper(trim($nim))))
            ->latest('id')
            ->paginate((int) ($v['per_page'] ?? 10))
            ->withQueryString();

        return $this->successResponse(
            data: PeminjamanResource::collection($paginator->items()),
            message: 'Daftar transaksi peminjaman berhasil diambil.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total_items' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
                'links' => [
                    'first' => $paginator->url(1),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                    'last' => $paginator->url($paginator->lastPage()),
                ],
            ]
        );
    }

    /**
     * GET /api/v1/peminjamans/{id} (Bearer Token)
     */
    public function show(string $id): JsonResponse
    {
        $peminjaman = Peminjaman::with('buku')->find($id);

        if (! $peminjaman) {
            return $this->notFound($id);
        }

        return $this->successResponse(
            data: new PeminjamanResource($peminjaman),
            message: 'Detail peminjaman berhasil ditemukan.'
        );
    }

    /**
     * POST /api/v1/peminjamans (Bearer Token)
     * Transaksi ACID: DB::transaction + lockForUpdate pada baris buku.
     *  - stok < 1                      -> 422 Unprocessable Content
     *  - NIM masih meminjam buku sama  -> 409 Conflict
     *  - lolos                         -> stok -1, simpan peminjaman, 201 Created + Location
     */
    public function store(Request $request): JsonResponse
    {
        $v = $request->validate([
            'buku_id' => ['required', 'integer', 'exists:bukus,id'],
            'nim_peminjam' => ['required', 'string', 'max:15'],
            'nama_peminjam' => ['required', 'string', 'max:150'],
            'durasi_hari' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        $nim = Str::upper(trim($v['nim_peminjam']));

        return DB::transaction(function () use ($v, $nim) {
            // Kunci baris buku secara eksklusif (SELECT ... FOR UPDATE) untuk menangkal Race Condition
            $buku = Buku::query()->where('id', $v['buku_id'])->lockForUpdate()->first();

            if (! $buku) {
                return $this->problemResponse(
                    title: 'Resource Not Found',
                    detail: "Buku dengan ID '{$v['buku_id']}' tidak ditemukan.",
                    status: 404,
                    errorSlug: 'book-not-found'
                );
            }

            if ($buku->stok < 1) {
                return $this->problemResponse(
                    title: 'Book Out of Stock',
                    detail: 'Stok buku sedang habis.',
                    status: 422,
                    errorSlug: 'book-out-of-stock'
                );
            }

            $sudahMeminjam = Peminjaman::query()
                ->where('buku_id', $buku->id)
                ->where('nim_peminjam', $nim)
                ->where('status', 'dipinjam')
                ->exists();

            if ($sudahMeminjam) {
                return $this->problemResponse(
                    title: 'Duplicate Active Loan',
                    detail: "NIM '{$nim}' masih meminjam buku ini dan belum mengembalikannya.",
                    status: 409,
                    errorSlug: 'duplicate-active-loan'
                );
            }

            $buku->decrement('stok');

            $peminjaman = Peminjaman::create([
                'kode_pinjam' => $this->generateKodePinjam(),
                'buku_id' => $buku->id,
                'nim_peminjam' => $nim,
                'nama_peminjam' => $v['nama_peminjam'],
                'tanggal_pinjam' => now()->toDateString(),
                'tanggal_jatuh_tempo' => now()->addDays((int) $v['durasi_hari'])->toDateString(),
                'status' => 'dipinjam',
            ]);

            $peminjaman->load('buku');

            return $this->createdResponse(
                data: new PeminjamanResource($peminjaman),
                locationUrl: url("/api/v1/peminjamans/{$peminjaman->id}"),
                message: "Peminjaman buku berhasil dicatat. Sisa stok: {$peminjaman->buku->stok}."
            );
        }, 3);
    }

    /**
     * PATCH /api/v1/peminjamans/{id}/kembalikan (Bearer Token)
     * Transaksi ACID: sudah dikembalikan -> 409; valid -> status dikembalikan, tanggal_kembali diisi, stok +1, 200 OK.
     */
    public function kembalikan(string $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $peminjaman = Peminjaman::query()->where('id', $id)->lockForUpdate()->first();

            if (! $peminjaman) {
                return $this->notFound($id);
            }

            if ($peminjaman->status === 'dikembalikan') {
                return $this->problemResponse(
                    title: 'Loan Already Returned',
                    detail: "Peminjaman '{$peminjaman->kode_pinjam}' sudah berstatus dikembalikan.",
                    status: 409,
                    errorSlug: 'loan-already-returned'
                );
            }

            $buku = Buku::query()->where('id', $peminjaman->buku_id)->lockForUpdate()->first();

            $peminjaman->update([
                'status' => 'dikembalikan',
                'tanggal_kembali' => now()->toDateString(),
            ]);

            $buku?->increment('stok');

            $peminjaman->load('buku');

            return $this->successResponse(
                data: new PeminjamanResource($peminjaman),
                message: 'Buku berhasil dikembalikan. Stok bertambah.'
            );
        }, 3);
    }

    private function notFound(string $id): JsonResponse
    {
        return $this->problemResponse(
            title: 'Resource Not Found',
            detail: "Peminjaman dengan ID '{$id}' tidak ditemukan.",
            status: 404,
            errorSlug: 'loan-not-found'
        );
    }

    private function generateKodePinjam(): string
    {
        return 'PINJAM-'.now()->format('Ymd').'-'.Str::upper(Str::random(12));
    }
}
