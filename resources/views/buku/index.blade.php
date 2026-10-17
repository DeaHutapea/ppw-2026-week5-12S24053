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

    <section class="card card-del overflow-hidden">
        <div class="card-body border-bottom">
            <form action="{{ route('buku.index') }}" method="GET" class="row g-2">
                <div class="col">
                    <label for="q" class="visually-hidden">Cari buku</label>
                    <input id="q" name="q" type="search" value="{{ request('q') }}" class="form-control" placeholder="Cari judul, penulis, atau ISBN">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-del">Cari</button>
                </div>
                @if (request()->filled('q'))
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
                            <td colspan="6" class="py-5 text-center">
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
