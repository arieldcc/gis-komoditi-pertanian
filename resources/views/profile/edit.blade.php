@extends('layouts.panel', [
    'pageTitle' => 'Profil Akun',
    'pageSubtitle' => 'Kelola data akun, email, password, dan penghapusan akun.',
])

@section('panel_content')
@if (session('status') === 'profile-updated')
    <div class="alert alert-success">Profil akun berhasil diperbarui.</div>
@endif
@if (session('status') === 'password-updated')
    <div class="alert alert-success">Password berhasil diperbarui.</div>
@endif

<div class="row g-3">
    <div class="col-12 col-xl-6">
        <div class="panel-card p-3 h-100">
            <h6 class="fw-semibold mb-3">Informasi Profil</h6>

            <div class="mb-3 small text-muted">
                Role aktif: <strong>{{ auth()->user()?->roleLabel() }}</strong>
            </div>

            <form method="post" action="{{ route('profile.update') }}" class="row g-2">
                @csrf
                @method('patch')

                <div class="col-12">
                    <label for="name" class="form-label">Nama</label>
                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-success">Simpan Profil</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="panel-card p-3 h-100">
            <h6 class="fw-semibold mb-3">Ubah Password</h6>

            @if ($errors->updatePassword->any())
                <div class="alert alert-warning py-2">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->updatePassword->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="{{ route('password.update') }}" class="row g-2">
                @csrf
                @method('put')

                <div class="col-12">
                    <label for="update_password_current_password" class="form-label">Password Saat Ini</label>
                    <input id="update_password_current_password" name="current_password" type="password" class="form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif" autocomplete="current-password">
                    @if($errors->updatePassword->has('current_password'))
                        <div class="invalid-feedback">{{ $errors->updatePassword->first('current_password') }}</div>
                    @endif
                </div>

                <div class="col-12">
                    <label for="update_password_password" class="form-label">Password Baru</label>
                    <input id="update_password_password" name="password" type="password" class="form-control @if($errors->updatePassword->has('password')) is-invalid @endif" autocomplete="new-password">
                    @if($errors->updatePassword->has('password'))
                        <div class="invalid-feedback">{{ $errors->updatePassword->first('password') }}</div>
                    @endif
                </div>

                <div class="col-12">
                    <label for="update_password_password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                    <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif" autocomplete="new-password">
                    @if($errors->updatePassword->has('password_confirmation'))
                        <div class="invalid-feedback">{{ $errors->updatePassword->first('password_confirmation') }}</div>
                    @endif
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Simpan Password</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12">
        <div class="panel-card p-3 border border-danger-subtle">
            <h6 class="fw-semibold text-danger mb-2">Hapus Akun</h6>
            <p class="text-muted mb-3">Aksi ini permanen. Semua data akses akun akan dihapus.</p>

            @if ($errors->userDeletion->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->userDeletion->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="{{ route('profile.destroy') }}" class="row g-2" onsubmit="return confirm('Yakin ingin menghapus akun ini secara permanen?');">
                @csrf
                @method('delete')

                <div class="col-md-6">
                    <label for="delete_password" class="form-label">Konfirmasi Password</label>
                    <input id="delete_password" name="password" type="password" class="form-control @if($errors->userDeletion->has('password')) is-invalid @endif" autocomplete="current-password" placeholder="Masukkan password untuk hapus akun">
                    @if($errors->userDeletion->has('password'))
                        <div class="invalid-feedback">{{ $errors->userDeletion->first('password') }}</div>
                    @endif
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-danger">Hapus Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
