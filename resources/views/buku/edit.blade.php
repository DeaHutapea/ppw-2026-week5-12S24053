<x-layout title="Ubah Buku">
    <div class="page-heading">
        <div>
            <p class="page-eyebrow">Koleksi</p>
            <h1 class="page-title">Ubah Data Buku</h1>
            <p class="page-description mb-0 mt-2">Perbarui informasi untuk {{ $buku->judul }}.</p>
        </div>
        <a href="{{ route('buku.show', $buku) }}" class="btn btn-outline-del">Lihat Buku</a>
    </div>

    <section class="card card-del">
        <div class="card-body p-4 p-lg-5">
            <form action="{{ route('buku.update', $buku) }}" method="POST">
                @csrf
                @method('PUT')
                @include('buku.form')
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('buku.index') }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-del">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </section>
</x-layout>
