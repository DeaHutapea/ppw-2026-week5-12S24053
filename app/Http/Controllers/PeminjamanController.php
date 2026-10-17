<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

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
        $validated['nim_peminjam'] = Str::upper(trim($validated['nim_peminjam']));

        try {
            DB::transaction(function () use ($validated): void {
                $buku = Buku::query()
                    ->where('id', $validated['buku_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($buku->stok < 1) {
                    throw new RuntimeException('Stok buku tidak tersedia untuk dipinjam.');
                }

                $sudahMeminjam = Peminjaman::query()
                    ->where('buku_id', $buku->id)
                    ->where('nim_peminjam', $validated['nim_peminjam'])
                    ->where('status', 'dipinjam')
                    ->exists();

                if ($sudahMeminjam) {
                    throw new RuntimeException('NIM ini masih meminjam buku yang sama dan belum mengembalikannya.');
                }

                $buku->decrement('stok');
                Peminjaman::create([
                    ...$validated,
                    'kode_pinjam' => $this->generateKodePinjam(),
                    'status' => 'dipinjam',
                ]);
            }, 3);
        } catch (QueryException $exception) {
            Log::error('[DB EXCEPTION] Gagal mencatat peminjaman buku: '.$exception->getMessage(), [
                'buku_id' => $validated['buku_id'],
                'nim_peminjam' => $validated['nim_peminjam'],
                'exception' => $exception,
            ]);

            return back()->withInput()->with('error', 'Peminjaman gagal disimpan karena terjadi masalah pada database.');
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        session(['last_borrower' => $validated['nim_peminjam'].' - '.$validated['nama_peminjam']]);

        return redirect()->route('peminjaman.index')->with('success', 'Peminjaman berhasil dicatat.');
    }

    public function kembali(Peminjaman $peminjaman): RedirectResponse
    {
        try {
            DB::transaction(function () use ($peminjaman): void {
                $lockedPeminjaman = Peminjaman::query()
                    ->lockForUpdate()
                    ->findOrFail($peminjaman->id);

                if ($lockedPeminjaman->status === 'dikembalikan') {
                    throw new RuntimeException('Buku ini sudah dikembalikan.');
                }

                $buku = Buku::query()
                    ->where('id', $lockedPeminjaman->buku_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedPeminjaman->update([
                    'tanggal_kembali' => now()->toDateString(),
                    'status' => 'dikembalikan',
                ]);
                $buku->increment('stok');
            }, 3);
        } catch (QueryException $exception) {
            Log::error('[DB EXCEPTION] Gagal mencatat pengembalian buku: '.$exception->getMessage(), [
                'peminjaman_id' => $peminjaman->id,
                'exception' => $exception,
            ]);

            return back()->withInput()->with('error', 'Pengembalian gagal disimpan karena terjadi masalah pada database.');
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('peminjaman.index')->with('success', 'Buku berhasil dikembalikan.');
    }

    private function generateKodePinjam(): string
    {
        return 'PINJAM-'.now()->format('Ymd').'-'.Str::upper(Str::random(12));
    }
}
