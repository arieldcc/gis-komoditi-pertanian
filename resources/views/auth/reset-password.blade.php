@extends('layouts.main')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-7 col-xl-6">
                <div class="form-wrapper p-4 p-lg-5 shadow-sm">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                        <div>
                            <h3 class="section-title mb-1">Buat Password Baru</h3>
                            <p class="text-muted mb-0">
                                Verifikasi akun sudah berhasil. Buat password baru dan selesaikan captcha untuk menyimpan perubahan.
                            </p>
                        </div>
                        <span class="badge text-bg-success-subtle text-success-emphasis border border-success-subtle">Langkah 2</span>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('password.store') }}" class="vstack gap-3">
                        @csrf

                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        <div>
                            <label for="email" class="form-label">Email Akun</label>
                            <input
                                id="email"
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                name="email"
                                value="{{ old('email', $request->email) }}"
                                required
                                autocomplete="username"
                                readonly
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="password" class="form-label">Password Baru</label>
                                <input
                                    id="password"
                                    type="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    name="password"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Minimal 8 karakter"
                                >
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    class="form-control @error('password_confirmation') is-invalid @enderror"
                                    name="password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    placeholder="Ulangi password baru"
                                >
                                @error('password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="rounded-3 border bg-light-subtle p-3">
                            <div class="fw-semibold mb-1">Captcha Konfirmasi</div>
                            <div class="text-muted small mb-2">Masukkan hasil penjumlahan berikut untuk menyelesaikan reset password.</div>
                            <label for="captcha_answer" class="form-label">Berapa hasil dari {{ $captchaQuestion }}?</label>
                            <input
                                id="captcha_answer"
                                type="text"
                                class="form-control @error('captcha_answer') is-invalid @enderror"
                                name="captcha_answer"
                                value="{{ old('captcha_answer') }}"
                                required
                                inputmode="numeric"
                                placeholder="Masukkan hasil penjumlahan"
                            >
                            @error('captcha_answer')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-between align-items-md-center">
                            <a href="{{ route('password.request') }}" class="btn btn-outline-secondary">Kembali</a>
                            <button type="submit" class="btn btn-success px-4">Simpan Password Baru</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
