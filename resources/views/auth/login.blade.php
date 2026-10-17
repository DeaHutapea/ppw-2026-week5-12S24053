<x-layout title="Login Staff">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card card-del shadow-sm border-0">
                <div class="card-body p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <div class="brand-mark brand-mark-lg mx-auto mb-3">D</div>
                        <h1 class="h3 mb-1">Login SIPUS-Del</h1>
                        <p class="text-muted-del mb-0">Masuk untuk mengelola koleksi dan peminjaman buku.</p>
                    </div>

                    <form method="POST" action="{{ route('login.submit') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email', request()->cookie('del_last_user_email')) }}" class="form-control @error('email') is-invalid @enderror" placeholder="staff@del.ac.id" required autofocus>
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password" required>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label" for="remember">Ingat saya</label>
                        </div>

                        <button type="submit" class="btn btn-del w-100">Masuk</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>
