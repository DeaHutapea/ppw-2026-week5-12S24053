<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KategoriController extends Controller
{
    public function index(): View
    {
        $kategoris = Kategori::withCount('bukus')
            ->orderBy('nama_kategori')
            ->simplePaginate(10);

        return view('kategori.index', compact('kategoris'));
    }

    public function create(): View
    {
        return view('kategori.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_kategori' => ['required', 'string', 'max:10', 'unique:kategoris,kode_kategori'],
            'nama_kategori' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        Kategori::create($validated);

        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function show(Kategori $kategori): View
    {
        $bukus = $kategori->bukus()->latest()->simplePaginate(10);

        return view('kategori.show', compact('kategori', 'bukus'));
    }

    public function edit(Kategori $kategori): View
    {
        return view('kategori.edit', compact('kategori'));
    }

    public function update(Request $request, Kategori $kategori): RedirectResponse
    {
        $validated = $request->validate([
            'kode_kategori' => [
                'required',
                'string',
                'max:10',
                Rule::unique('kategoris', 'kode_kategori')->ignore($kategori->id),
            ],
            'nama_kategori' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $kategori->update($validated);

        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Kategori $kategori): RedirectResponse
    {
        if ($kategori->bukus()->exists()) {
            return back()->with('error', 'Kategori tidak dapat dihapus selama masih memiliki buku.');
        }

        $kategori->delete();

        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
