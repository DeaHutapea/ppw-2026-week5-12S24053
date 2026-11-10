<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BukuResource;
use App\Models\Buku;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BukuApiController extends Controller
{
    use ApiResponse;

    /** Kolom yang boleh dipakai untuk ?sort= (whitelist, mencegah ORDER BY liar). */
    private const SORTABLE = ['judul', 'penulis', 'isbn', 'tahun_terbit', 'stok', 'created_at'];

    /**
     * GET /api/v1/bukus
     * Query: search, kategori_id, tersedia=1, sort, dir, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $v = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'kategori_id' => ['nullable', 'integer', 'exists:kategoris,id'],
            'tersedia' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(self::SORTABLE)],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $sort = $v['sort'] ?? 'judul';
        $dir = $v['dir'] ?? 'asc';
        $perPage = (int) ($v['per_page'] ?? 10);

        // with('kategori') + withCount('peminjamans') => bebas N+1
        $paginator = Buku::query()
            ->with('kategori')
            ->withCount('peminjamans')
            ->when($v['search'] ?? null, function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('judul', 'like', "%{$search}%")
                        ->orWhere('penulis', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%");
                });
            })
            ->when($v['kategori_id'] ?? null, fn ($query, $id) => $query->where('kategori_id', $id))
            ->when($request->boolean('tersedia'), fn ($query) => $query->where('stok', '>', 0))
            ->orderBy($sort, $dir)
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse(
            data: BukuResource::collection($paginator->items()),
            message: 'Daftar koleksi buku berhasil diambil.',
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
     * GET /api/v1/bukus/{id}
     * Detail buku + kategori + 20 riwayat peminjaman terbaru, atau 404 RFC 7807.
     */
    public function show(string $id): JsonResponse
    {
        $buku = Buku::query()
            ->with([
                'kategori',
                'peminjamans' => fn ($q) => $q->latest('id')->limit(20),
            ])
            ->withCount('peminjamans')
            ->find($id);

        if (! $buku) {
            return $this->notFound($id);
        }

        return $this->successResponse(
            data: new BukuResource($buku),
            message: 'Detail buku berhasil ditemukan.'
        );
    }

    /**
     * POST /api/v1/bukus (Bearer Token)
     * Membuat buku baru: 201 Created + header Location.
     */
    public function store(Request $request): JsonResponse
    {
        $buku = Buku::create($request->validate($this->rules()));
        $buku->load('kategori')->loadCount('peminjamans');

        return $this->createdResponse(
            data: new BukuResource($buku),
            locationUrl: url("/api/v1/bukus/{$buku->id}"),
            message: 'Buku baru berhasil dibuat.'
        );
    }

    /**
     * PUT /api/v1/bukus/{id}   -> Full Replacement (semua atribut wajib dikirim)
     * PATCH /api/v1/bukus/{id} -> Partial Update (hanya atribut yang dikirim)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (! $buku) {
            return $this->notFound($id);
        }

        if ($request->isMethod('put')) {
            // PUT: seluruh representasi wajib lengkap; field nullable yang tidak dikirim direset ke null
            $validated = $request->validate($this->rules($buku));
            $validated['sinopsis'] = $validated['sinopsis'] ?? null;
        } else {
            // PATCH: validasi & ubah hanya field yang ada di body (sometimes|required)
            $validated = $request->validate($this->rules($buku, partial: true));

            if (empty($validated)) {
                return $this->problemResponse(
                    title: 'Bad Request',
                    detail: 'Permintaan PATCH wajib menyertakan minimal satu atribut yang ingin diperbarui.',
                    status: 400,
                    errorSlug: 'empty-patch-payload'
                );
            }
        }

        $buku->update($validated);
        $buku->load('kategori')->loadCount('peminjamans');

        return $this->successResponse(
            data: new BukuResource($buku),
            message: $request->isMethod('put')
                ? 'Seluruh representasi buku berhasil diganti (PUT).'
                : 'Atribut buku berhasil diperbarui sebagian (PATCH).'
        );
    }

    /**
     * DELETE /api/v1/bukus/{id} (Bearer Token)
     * 409 Conflict jika ada peminjaman aktif (status = dipinjam), 204 No Content jika berhasil.
     */
    public function destroy(string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (! $buku) {
            return $this->notFound($id);
        }

        $aktif = $buku->peminjamans()->where('status', 'dipinjam')->count();

        if ($aktif > 0) {
            return $this->problemResponse(
                title: 'Conflict: Book Has Active Loans',
                detail: "Buku '{$buku->judul}' tidak dapat dihapus karena masih dipinjam ({$aktif} peminjaman aktif).",
                status: 409,
                errorSlug: 'book-has-active-loans'
            );
        }

        $buku->delete();

        return $this->noContentResponse();
    }

    private function notFound(string $id): JsonResponse
    {
        return $this->problemResponse(
            title: 'Resource Not Found',
            detail: "Buku dengan ID '{$id}' tidak ditemukan.",
            status: 404,
            errorSlug: 'book-not-found'
        );
    }

    /**
     * Aturan validasi (disamakan dengan BukuController web).
     * $partial = true (PATCH) -> setiap field diberi 'sometimes'.
     */
    private function rules(?Buku $buku = null, bool $partial = false): array
    {
        $rules = [
            'isbn' => [
                'required', 'string', 'size:13', 'regex:/^\d{13}$/',
                Rule::unique('bukus', 'isbn')->ignore($buku?->id),
            ],
            'judul' => ['required', 'string', 'min:5', 'max:200'],
            'penulis' => ['required', 'string', 'max:150'],
            'penerbit' => ['required', 'string', 'max:150'],
            'tahun_terbit' => ['required', 'integer', 'between:1000,'.now()->year],
            'kategori_id' => ['required', 'integer', 'exists:kategoris,id'],
            'stok' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'sinopsis' => ['nullable', 'string', 'max:5000'],
        ];

        if ($partial) {
            foreach ($rules as $field => $fieldRules) {
                array_unshift($rules[$field], 'sometimes');
            }
        }

        return $rules;
    }
}