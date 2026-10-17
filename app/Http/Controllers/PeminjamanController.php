<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PeminjamanController extends Controller
{
    public function index(): View
    {
        $peminjamans = Peminjaman::with('buku')->latest()->paginate(10);

        return view('peminjaman.index', compact('peminjamans'));
    }

    public function create(): View
    {
        $bukus = Buku::where('stok', '>', 0)->orderBy('judul')->get();

        return view('peminjaman.create', compact('bukus'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'buku_id' => ['required', 'integer', 'exists:bukus,id'],
            'nim_peminjam' => ['required', 'string', 'max:15'],
            'nama_peminjam' => ['required', 'string', 'max:150'],
            'tanggal_pinjam' => ['required', 'date'],
            'tanggal_jatuh_tempo' => ['required', 'date', 'after_or_equal:tanggal_pinjam'],
        ]);

        $borrowed = DB::transaction(function () use ($validated): bool {
            $buku = Buku::query()
                ->lockForUpdate()
                ->findOrFail($validated['buku_id']);

            if ($buku->stok < 1) {
                return false;
            }

            $buku->decrement('stok');
            Peminjaman::create([
                ...$validated,
                'kode_pinjam' => $this->generateKodePinjam(),
                'status' => 'dipinjam',
            ]);

            return true;
        });

        if (! $borrowed) {
            return back()->with('error', 'Stok buku tidak tersedia untuk dipinjam.')->withInput();
        }

        return redirect()->route('peminjaman.index')->with('success', 'Peminjaman berhasil dicatat.');
    }

    public function kembali(Peminjaman $peminjaman): RedirectResponse
    {
        $returned = DB::transaction(function () use ($peminjaman): bool {
            $lockedPeminjaman = Peminjaman::query()
                ->lockForUpdate()
                ->findOrFail($peminjaman->id);

            if ($lockedPeminjaman->status === 'kembali') {
                return false;
            }

            $buku = Buku::query()
                ->lockForUpdate()
                ->findOrFail($lockedPeminjaman->buku_id);

            $lockedPeminjaman->update([
                'tanggal_kembali' => now()->toDateString(),
                'status' => 'kembali',
            ]);
            $buku->increment('stok');

            return true;
        });

        if (! $returned) {
            return back()->with('error', 'Buku ini sudah dikembalikan.');
        }

        return redirect()->route('peminjaman.index')->with('success', 'Buku berhasil dikembalikan.');
    }

    private function generateKodePinjam(): string
    {
        return 'PINJAM-'.now()->format('Ymd').'-'.Str::upper(Str::random(12));
    }
}
