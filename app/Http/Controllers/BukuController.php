<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BukuController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori_id' => ['nullable', 'integer', 'exists:kategoris,id'],
        ]);
        $search = $validated['q'] ?? '';
        $kategoriId = $validated['kategori_id'] ?? null;

        if ($search !== '') {
            $request->session()->put('last_book_search', $search);
        }

        $bukus = Buku::with('kategori')
            ->withCount('peminjamans')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('judul', 'like', "%{$search}%")
                        ->orWhere('penulis', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%");
                });
            })
            ->when($kategoriId !== null, fn ($query) => $query->where('kategori_id', $kategoriId))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $kategoris = Kategori::query()->orderBy('nama_kategori')->get();
        $stats = [
            'total_judul' => DB::table('bukus')->count(),
            'total_stok' => (int) DB::table('bukus')->sum('stok'),
            'total_dipinjam' => DB::table('peminjamans')->where('status', 'dipinjam')->count(),
            'total_dikembalikan' => DB::table('peminjamans')->where('status', 'dikembalikan')->count(),
        ];

        $lastSearch = session('last_book_search');

        return view('buku.index', compact('bukus', 'stats', 'kategoris', 'lastSearch'));
    }

    public function create(): View
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('buku.create', compact('kategoris'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        Buku::create($validated);

        return redirect()->route('buku.index')->with('success', 'Buku berhasil ditambahkan.');
    }

    public function show(Buku $buku): View
    {
        $buku->load('kategori');

        return view('buku.show', compact('buku'));
    }

    public function edit(Buku $buku): View
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();

        return view('buku.edit', compact('buku', 'kategoris'));
    }

    public function update(Request $request, Buku $buku): RedirectResponse
    {
        $validated = $request->validate($this->rules($buku));

        $buku->update($validated);

        return redirect()->route('buku.index')->with('success', 'Data buku berhasil diperbarui.');
    }

    public function destroy(Buku $buku): RedirectResponse
    {
        if ($buku->peminjamans()->exists()) {
            return back()->with('error', 'Buku tidak dapat dihapus karena memiliki riwayat peminjaman.');
        }

        $buku->delete();

        return redirect()->route('buku.index')->with('success', 'Buku berhasil dihapus.');
    }

    private function rules(?Buku $buku = null): array
    {
        return [
            'isbn' => [
                'required',
                'string',
                'size:13',
                'regex:/^\d{13}$/',
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
    }
}
