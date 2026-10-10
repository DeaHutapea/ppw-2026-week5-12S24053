<div class="row g-3">
    <div class="col-md-6">
        <label for="isbn" class="form-label">ISBN</label>
        <input id="isbn" name="isbn" type="text" inputmode="numeric" maxlength="13" value="{{ old('isbn', $buku->isbn ?? '') }}" class="form-control @error('isbn') is-invalid @enderror" required>
        <div class="form-text">Masukkan 13 digit ISBN tanpa tanda hubung.</div>
        @error('isbn')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="judul" class="form-label">Judul</label>
        <input id="judul" name="judul" type="text" maxlength="200" value="{{ old('judul', $buku->judul ?? '') }}" class="form-control @error('judul') is-invalid @enderror" required>
        @error('judul')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="penulis" class="form-label">Penulis</label>
        <input id="penulis" name="penulis" type="text" maxlength="150" value="{{ old('penulis', $buku->penulis ?? '') }}" class="form-control @error('penulis') is-invalid @enderror" required>
        @error('penulis')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="penerbit" class="form-label">Penerbit</label>
        <input id="penerbit" name="penerbit" type="text" maxlength="150" value="{{ old('penerbit', $buku->penerbit ?? '') }}" class="form-control @error('penerbit') is-invalid @enderror" required>
        @error('penerbit')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="tahun_terbit" class="form-label">Tahun Terbit</label>
        <input id="tahun_terbit" name="tahun_terbit" type="number" min="1000" max="{{ now()->year }}" value="{{ old('tahun_terbit', $buku->tahun_terbit ?? '') }}" class="form-control @error('tahun_terbit') is-invalid @enderror" required>
        @error('tahun_terbit')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="kategori_id" class="form-label">Kategori</label>
        <select id="kategori_id" name="kategori_id" class="form-select @error('kategori_id') is-invalid @enderror" required>
            <option value="">Pilih kategori</option>
            @foreach ($kategoris as $kategori)
                <option value="{{ $kategori->id }}" @selected((string) old('kategori_id', $buku->kategori_id ?? '') === (string) $kategori->id)>
                    {{ $kategori->nama_kategori }}
                </option>
            @endforeach
        </select>
        @error('kategori_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if ($kategoris->isEmpty())
            <div class="form-text">Buat <a href="{{ route('kategori.create') }}">kategori</a> sebelum menambahkan buku.</div>
        @endif
    </div>
    <div class="col-md-4">
        <label for="stok" class="form-label">Stok</label>
        <input id="stok" name="stok" type="number" min="0" value="{{ old('stok', $buku->stok ?? 0) }}" class="form-control @error('stok') is-invalid @enderror" required>
        @error('stok')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label for="sinopsis" class="form-label">Sinopsis <span class="text-muted-del fw-normal">(opsional)</span></label>
        <textarea id="sinopsis" name="sinopsis" rows="5" maxlength="5000" class="form-control @error('sinopsis') is-invalid @enderror">{{ old('sinopsis', $buku->sinopsis ?? '') }}</textarea>
        @error('sinopsis')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
