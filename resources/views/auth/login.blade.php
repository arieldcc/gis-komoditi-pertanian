@extends('layouts.main')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="form-wrapper p-4 p-lg-5 shadow-sm">
                    <h3 class="section-title mb-1">Login Sistem</h3>
                    <p class="text-muted mb-4">Masuk sesuai peran Anda untuk mengakses modul SIG komoditas pertanian.</p>

                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="vstack gap-3">
                        @csrf

                        <div>
                            <label for="email" class="form-label">Email</label>
                            <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                        </div>

                        <div>
                            <label for="password" class="form-label">Password</label>
                            <input id="password" type="password" class="form-control" name="password" required autocomplete="current-password">
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Ingat saya</label>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Login</button>
                    </form>

                    <div class="mt-4 small text-muted">
                        Akun default seeder: admin@sigkomoditas.id / Admin12345!
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
