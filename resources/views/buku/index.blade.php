<x-layout title="Koleksi Buku">
    <div class="page-heading">
        <div>
            <p class="page-eyebrow">Perpustakaan Kampus Del</p>
            <h1 class="page-title">Koleksi Buku</h1>
            <p class="page-description mb-0 mt-2">Jelajahi dan kelola koleksi buku perpustakaan.</p>
        </div>
        @auth
            <a href="{{ route('buku.create') }}" class="btn btn-del px-3 py-2">Tambah Buku</a>
        @endauth
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card card-del h-100"><div class="card-body">
                <p class="detail-label mb-1">Total Judul Buku</p>
                <p class="h3 mb-0">{{ number_format($stats['total_judul']) }}</p>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card card-del h-100"><div class="card-body">
                <p class="detail-label mb-1">Total Stok Tersedia</p>
                <p class="h3 mb-0">{{ number_format($stats['total_stok']) }}</p>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card card-del h-100"><div class="card-body">
                <p class="detail-label mb-1">Sedang Dipinjam</p>
                <p class="h3 mb-0">{{ number_format($stats['total_dipinjam']) }}</p>
            </div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card card-del h-100"><div class="card-body">
                <p class="detail-label mb-1">Total Dikembalikan</p>
                <p class="h3 mb-0">{{ number_format($stats['total_dikembalikan']) }}</p>
            </div></div>
        </div>
    </div>

    @if ($lastSearch && ! request()->filled('q'))
        <p class="mb-3">Pencarian terakhir Anda:
            <a href="{{ route('buku.index', ['q' => $lastSearch]) }}">{{ $lastSearch }}</a>
        </p>
    @endif

    <section class="card card-del overflow-hidden">
        <div class="card-body border-bottom">
            <form action="{{ route('buku.index') }}" method="GET" class="row g-2">
                <div class="col-md">
                    <label for="q" class="visually-hidden">Cari buku</label>
                    <input id="q" name="q" type="search" value="{{ request('q') }}" class="form-control" placeholder="Cari judul, penulis, atau ISBN">
                </div>
                <div class="col-md">
                    <label for="kategori_id" class="visually-hidden">Filter kategori</label>
                    <select id="kategori_id" name="kategori_id" class="form-select">
                        <option value="">Semua kategori</option>
                        @foreach ($kategoris as $kategori)
                            <option value="{{ $kategori->id }}" @selected(request('kategori_id') == $kategori->id)>
                                {{ $kategori->nama_kategori }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-del">Cari</button>
                </div>
                @if (request()->filled('q') || request()->filled('kategori_id'))
                    <div class="col-auto">
                        <a href="{{ route('buku.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                @endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th scope="col">Buku</th>
                        <th scope="col">ISBN</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Tahun</th>
                        <th scope="col">Stok</th>
                        <th scope="col">Dipinjam (kali)</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bukus as $buku)
                        <tr>
                            <td>
                                <a class="fw-semibold text-decoration-none text-dark" href="{{ route('buku.show', $buku) }}">{{ $buku->judul }}</a>
                                <div class="small text-muted-del">{{ $buku->penulis }} &middot; {{ $buku->penerbit }}</div>
                            </td>
                            <td class="text-nowrap">{{ $buku->isbn }}</td>
                            <td><span class="badge rounded-pill badge-stock">{{ $buku->kategori->nama_kategori }}</span></td>
                            <td>{{ $buku->tahun_terbit }}</td>
                            <td>{{ number_format($buku->stok) }}</td>
                            <td>{{ number_format($buku->peminjamans_count) }}</td>
                            <td class="text-end text-nowrap">
                                @auth
                                    <a href="{{ route('buku.edit', $buku) }}" class="btn btn-sm btn-outline-del">Ubah</a>
                                    <form action="{{ route('buku.destroy', $buku) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus buku ini dari katalog?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                @else
                                    <span class="text-muted-del">-</span>
                                @endauth
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-5 text-center">
                                <p class="fw-semibold mb-1">Belum ada buku di katalog.</p>
                                <p class="small text-muted-del mb-3">Tambahkan buku pertama untuk memulai koleksi.</p>
                                @auth
                                    <a href="{{ route('buku.create') }}" class="btn btn-sm btn-del">Tambah Buku</a>
                                @endauth
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($bukus->hasPages())
            <div class="card-body border-top">{{ $bukus->links() }}</div>
        @endif
    </section>
</x-layout>
