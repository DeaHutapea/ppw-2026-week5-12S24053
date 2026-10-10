<x-layout title="Kategori Buku">
    <div class="page-heading">
        <div>
            <p class="page-eyebrow">Pengaturan Koleksi</p>
            <h1 class="page-title">Kategori Buku</h1>
            <p class="page-description mb-0 mt-2">Atur kelompok buku yang tersedia di perpustakaan.</p>
        </div>
        <a href="{{ route('kategori.create') }}" class="btn btn-del px-3 py-2">Tambah Kategori</a>
    </div>

    <section class="card card-del overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th scope="col">Kode</th>
                        <th scope="col">Nama Kategori</th>
                        <th scope="col">Jumlah Buku</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kategoris as $kategori)
                        <tr>
                            <td class="fw-semibold">{{ $kategori->kode_kategori }}</td>
                            <td><a class="text-decoration-none text-dark fw-semibold" href="{{ route('kategori.show', $kategori) }}">{{ $kategori->nama_kategori }}</a></td>
                            <td>{{ $kategori->bukus_count }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('kategori.edit', $kategori) }}" class="btn btn-sm btn-outline-del">Ubah</a>
                                <form action="{{ route('kategori.destroy', $kategori) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kategori ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" @disabled($kategori->bukus_count > 0) title="{{ $kategori->bukus_count > 0 ? 'Pindahkan buku ke kategori lain sebelum menghapus.' : 'Hapus kategori' }}">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-5 text-center">
                                <p class="fw-semibold mb-1">Belum ada kategori.</p>
                                <p class="small text-muted-del mb-3">Tambahkan kategori agar buku dapat dikelompokkan.</p>
                                <a href="{{ route('kategori.create') }}" class="btn btn-sm btn-del">Tambah Kategori</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($kategoris->hasPages())
            <div class="card-body border-top">{{ $kategoris->links() }}</div>
        @endif
    </section>
</x-layout>
