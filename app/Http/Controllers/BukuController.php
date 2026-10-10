<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BukuController extends Controller
{
    public function index(): View
    {
        $bukus = Buku::with('kategori')
            ->latest()
            ->simplePaginate(10);

        return view('buku.index', compact('bukus'));
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
