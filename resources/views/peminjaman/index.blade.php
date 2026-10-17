<x-layout title="Peminjaman">
    <div class="page-heading">
        <div>
            <p class="page-eyebrow">Transaksi</p>
            <h1 class="page-title">Peminjaman Buku</h1>
            <p class="page-description mb-0 mt-2">Kelola daftar peminjaman dan status pengembalian.</p>
        </div>
        <a href="{{ route('peminjaman.create') }}" class="btn btn-del px-3 py-2">Tambah Peminjaman</a>
    </div>

    <section class="card card-del overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Buku</th>
                        <th>Peminjam</th>
                        <th>Pinjam</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($peminjamans as $peminjaman)
                        <tr>
                            <td class="fw-semibold">{{ $peminjaman->kode_pinjam }}</td>
                            <td>{{ $peminjaman->buku->judul }}</td>
                            <td>
                                <div class="fw-semibold">{{ $peminjaman->nama_peminjam }}</div>
                                <small class="text-muted-del">NIM: {{ $peminjaman->nim_peminjam }}</small>
                            </td>
                            <td>{{ $peminjaman->tanggal_pinjam->format('d-m-Y') }}</td>
                            <td>{{ $peminjaman->tanggal_jatuh_tempo->format('d-m-Y') }}</td>
                            <td>
                                @if ($peminjaman->status === 'kembali')
                                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Kembali</span>
                                @else
                                    <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">Dipinjam</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($peminjaman->status !== 'kembali')
                                    <form action="{{ route('peminjaman.kembali', $peminjaman) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">Kembalikan</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-5 text-center">
                                <p class="fw-semibold mb-1">Belum ada data peminjaman.</p>
                                <p class="small text-muted-del mb-3">Catat peminjaman pertama untuk memulai transaksi.</p>
                                <a href="{{ route('peminjaman.create') }}" class="btn btn-sm btn-del">Tambah Peminjaman</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($peminjamans->hasPages())
            <div class="card-body border-top">{{ $peminjamans->links() }}</div>
        @endif
    </section>
</x-layout>
