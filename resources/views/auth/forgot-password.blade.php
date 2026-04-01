@extends('layouts.main')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-6 col-xl-5">
                <div class="form-wrapper p-4 p-lg-5 shadow-sm">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                        <div>
                            <h3 class="section-title mb-1">Reset Password</h3>
                            <p class="text-muted mb-0">
                                Masukkan email akun dan jawab captcha sederhana untuk melanjutkan reset password tanpa email.
                            </p>
                        </div>
                        <span class="badge text-bg-success-subtle text-success-emphasis border border-success-subtle">Langkah 1</span>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="vstack gap-3">
                        @csrf

                        <div>
                            <label for="email" class="form-label">Email Akun</label>
                            <input
                                id="email"
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="contoh: admin@sigkomoditas.id"
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="rounded-3 border bg-light-subtle p-3">
                            <div class="fw-semibold mb-1">Verifikasi Captcha</div>
                            <div class="text-muted small mb-2">Hitung penjumlahan berikut lalu isi jawabannya.</div>
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
                            <a href="{{ route('login') }}" class="btn btn-outline-secondary">Kembali ke Login</a>
                            <button type="submit" class="btn btn-success px-4">Lanjut Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
