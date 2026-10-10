<x-layout title="Ubah Kategori">
    <div class="page-heading">
        <div>
            <p class="page-eyebrow">Pengaturan Koleksi</p>
            <h1 class="page-title">Ubah Kategori</h1>
            <p class="page-description mb-0 mt-2">Perbarui informasi {{ $kategori->nama_kategori }}.</p>
        </div>
        <a href="{{ route('kategori.show', $kategori) }}" class="btn btn-outline-del">Lihat Kategori</a>
    </div>

    <section class="card card-del">
        <div class="card-body p-4 p-lg-5">
            <form action="{{ route('kategori.update', $kategori) }}" method="POST">
                @csrf
                @method('PUT')
                @include('kategori.form')
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('kategori.index') }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-del">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </section>
</x-layout>
