<x-layout title="Tambah Peminjaman">
    <div class="page-heading">
        <div>
            <p class="page-eyebrow">Transaksi</p>
            <h1 class="page-title">Tambah Peminjaman</h1>
            <p class="page-description mb-0 mt-2">Catat data peminjaman buku baru.</p>
        </div>
        <a href="{{ route('peminjaman.index') }}" class="btn btn-outline-del px-3 py-2">Kembali</a>
    </div>

    <section class="card card-del">
        <div class="card-body">
            <form action="{{ route('peminjaman.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="buku_id" class="form-label">Buku</label>
                        <select id="buku_id" name="buku_id" class="form-select @error('buku_id') is-invalid @enderror" required>
                            <option value="">Pilih buku</option>
                            @foreach ($bukus as $buku)
                                <option value="{{ $buku->id }}" @selected(old('buku_id') == $buku->id)>
                                    {{ $buku->judul }} (stok: {{ $buku->stok }})
                                </option>
                            @endforeach
                        </select>
                        @error('buku_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="nim_peminjam" class="form-label">NIM Peminjam</label>
                        <input id="nim_peminjam" name="nim_peminjam" type="text" maxlength="15" value="{{ old('nim_peminjam') }}" class="form-control @error('nim_peminjam') is-invalid @enderror" required>
                        @error('nim_peminjam')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="nama_peminjam" class="form-label">Nama Peminjam</label>
                        <input id="nama_peminjam" name="nama_peminjam" type="text" maxlength="150" value="{{ old('nama_peminjam') }}" class="form-control @error('nama_peminjam') is-invalid @enderror" required>
                        @error('nama_peminjam')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="tanggal_pinjam" class="form-label">Tanggal Pinjam</label>
                        <input id="tanggal_pinjam" name="tanggal_pinjam" type="date" value="{{ old('tanggal_pinjam', now()->toDateString()) }}" class="form-control @error('tanggal_pinjam') is-invalid @enderror" required>
                        @error('tanggal_pinjam')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="tanggal_jatuh_tempo" class="form-label">Tanggal Jatuh Tempo</label>
                        <input id="tanggal_jatuh_tempo" name="tanggal_jatuh_tempo" type="date" value="{{ old('tanggal_jatuh_tempo', now()->addDays(7)->toDateString()) }}" class="form-control @error('tanggal_jatuh_tempo') is-invalid @enderror" required>
                        @error('tanggal_jatuh_tempo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('peminjaman.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-del">Simpan Peminjaman</button>
                </div>
            </form>
        </div>
    </section>
</x-layout>
