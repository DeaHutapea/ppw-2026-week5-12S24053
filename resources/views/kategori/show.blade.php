<x-layout :title="$kategori->nama_kategori">
    <div class="page-heading">
        <div>
            <p class="page-eyebrow">Kategori Buku</p>
            <h1 class="page-title">{{ $kategori->nama_kategori }}</h1>
            <p class="page-description mb-0 mt-2">Kode kategori: {{ $kategori->kode_kategori }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('kategori.index') }}" class="btn btn-outline-del">Kembali</a>
            <a href="{{ route('kategori.edit', $kategori) }}" class="btn btn-del">Ubah Kategori</a>
        </div>
    </div>

    <section class="card card-del overflow-hidden">
        <div class="card-header bg-white border-0 px-4 pt-4">
            <h2 class="h5 mb-0">Buku dalam kategori ini</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th scope="col">Judul</th>
                        <th scope="col">Penulis</th>
                        <th scope="col">ISBN</th>
                        <th scope="col">Tahun</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bukus as $buku)
                        <tr>
                            <td><a href="{{ route('buku.show', $buku) }}" class="text-decoration-none fw-semibold text-dark">{{ $buku->judul }}</a></td>
                            <td>{{ $buku->penulis }}</td>
                            <td>{{ $buku->isbn }}</td>
                            <td>{{ $buku->tahun_terbit }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-5 text-center text-muted-del">Belum ada buku pada kategori ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($bukus->hasPages())
            <div class="card-body border-top">{{ $bukus->links() }}</div>
        @endif
    </section>
</x-layout>
