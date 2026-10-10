@props(['title' => 'Katalog'])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | SIPUS-Del</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-del shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('buku.index') }}">
                <span class="brand-mark">D</span>
                <span>SIPUS-Del</span>
            </a>
            <button class="navbar-toggler border-light" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Buka navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavigation">
                <div class="navbar-nav ms-auto gap-lg-2">
                    <a class="nav-link {{ request()->routeIs('buku.*') ? 'active' : '' }}" href="{{ route('buku.index') }}">Koleksi Buku</a>
                    <a class="nav-link {{ request()->routeIs('kategori.*') ? 'active' : '' }}" href="{{ route('kategori.index') }}">Kategori</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4 py-lg-5">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="status">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <p class="mb-1 fw-semibold">Periksa kembali data yang dimasukkan:</p>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="container pb-4 site-footer">
        Sistem Informasi Perpustakaan Kampus Del
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
