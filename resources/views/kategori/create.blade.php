<x-layout title="Tambah Kategori">
    <div class="page-heading">
        <div>
            <p class="page-eyebrow">Pengaturan Koleksi</p>
            <h1 class="page-title">Tambah Kategori</h1>
            <p class="page-description mb-0 mt-2">Buat kategori baru untuk mengelompokkan buku.</p>
        </div>
        <a href="{{ route('kategori.index') }}" class="btn btn-outline-del">Kembali ke Kategori</a>
    </div>

    <section class="card card-del">
        <div class="card-body p-4 p-lg-5">
            <form action="{{ route('kategori.store') }}" method="POST">
                @csrf
                @include('kategori.form')
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('kategori.index') }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-del">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </section>
</x-layout>
