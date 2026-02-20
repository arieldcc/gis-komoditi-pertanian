<div class="col-md-6">
    <label class="form-label">Nama</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $user?->name) }}" required>
</div>
<div class="col-md-6">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" value="{{ old('email', $user?->email) }}" required>
</div>
<div class="col-md-6">
    <label class="form-label">Role</label>
    <select class="form-select" name="role" required>
        <option value="">Pilih Role</option>
        @foreach($roles as $role)
            <option value="{{ $role }}" @selected(old('role', $user?->role) === $role)>{{ ucwords(str_replace('_', ' ', $role)) }}</option>
        @endforeach
    </select>
    <small class="text-muted">Gunakan menu ini untuk akun pusat/pimpinan. Akun admin kecamatan dibuat dari menu Balai, akun penyuluh dibuat dari menu Penyuluh.</small>
</div>
<div class="col-md-6 d-flex align-items-end">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $user?->is_active ?? true))>
        <label class="form-check-label" for="is_active">Akun aktif</label>
    </div>
</div>
<div class="col-md-6">
    <label class="form-label">Password {{ $user ? '(opsional)' : '' }}</label>
    <input type="password" name="password" class="form-control" {{ $user ? '' : 'required' }}>
</div>
<div class="col-md-6">
    <label class="form-label">Konfirmasi Password</label>
    <input type="password" name="password_confirmation" class="form-control" {{ $user ? '' : 'required' }}>
</div>
<div class="col-12 d-flex gap-2">
    <button class="btn btn-success" type="submit">Simpan</button>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Batal</a>
</div>
