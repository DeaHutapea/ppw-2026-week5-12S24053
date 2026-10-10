<x-layout :title="$buku->judul">
    <div class="page-heading">
        <div>
            <p class="page-eyebrow">Detail Koleksi</p>
            <h1 class="page-title">{{ $buku->judul }}</h1>
            <p class="page-description mb-0 mt-2">{{ $buku->penulis }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('buku.index') }}" class="btn btn-outline-del">Kembali</a>
            <a href="{{ route('buku.edit', $buku) }}" class="btn btn-del">Ubah Buku</a>
        </div>
    </div>

    <section class="card card-del">
        <div class="card-body p-4 p-lg-5">
            <div class="row g-4">
                <div class="col-6 col-lg-3">
                    <p class="detail-label">ISBN</p>
                    <p class="detail-value">{{ $buku->isbn }}</p>
                </div>
                <div class="col-6 col-lg-3">
                    <p class="detail-label">Kategori</p>
                    <p class="detail-value">{{ $buku->kategori->nama_kategori }}</p>
                </div>
                <div class="col-6 col-lg-3">
                    <p class="detail-label">Penerbit</p>
                    <p class="detail-value">{{ $buku->penerbit }}</p>
                </div>
                <div class="col-6 col-lg-3">
                    <p class="detail-label">Tahun Terbit</p>
                    <p class="detail-value">{{ $buku->tahun_terbit }}</p>
                </div>
                <div class="col-6 col-lg-3">
                    <p class="detail-label">Stok Tersedia</p>
                    <p class="detail-value">{{ number_format($buku->stok) }} eksemplar</p>
                </div>
                <div class="col-12">
                    <p class="detail-label">Sinopsis</p>
                    <p class="mb-0">{!! nl2br(e($buku->sinopsis ?: 'Belum ada sinopsis.')) !!}</p>
                </div>
            </div>
        </div>
    </section>
</x-layout>
