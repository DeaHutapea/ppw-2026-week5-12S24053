<x-layout title="Tambah Buku">
    <div class="page-heading">
        <div>
            <p class="page-eyebrow">Koleksi</p>
            <h1 class="page-title">Tambah Buku</h1>
            <p class="page-description mb-0 mt-2">Masukkan informasi buku baru ke dalam katalog.</p>
        </div>
        <a href="{{ route('buku.index') }}" class="btn btn-outline-del">Kembali ke Koleksi</a>
    </div>

    <section class="card card-del">
        <div class="card-body p-4 p-lg-5">
            <form action="{{ route('buku.store') }}" method="POST">
                @csrf
                @include('buku.form')
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('buku.index') }}" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-del">Simpan Buku</button>
                </div>
            </form>
        </div>
    </section>
</x-layout>
