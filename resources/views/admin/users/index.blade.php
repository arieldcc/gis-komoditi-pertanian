@extends('layouts.panel', [
    'pageTitle' => 'Manajemen User',
    'pageSubtitle' => 'Kelola akun dan role pengguna sistem.',
])

@section('panel_content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h6 class="mb-1 fw-semibold">Daftar Pengguna</h6>
        <p class="text-muted mb-0">Gunakan filter untuk mencari user berdasarkan nama, email, atau role.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-success">Tambah User</a>
</div>

<div class="table-responsive panel-card p-2">
    <table class="table table-hover align-middle mb-0" data-dt-server="true" data-dt-source="admin_users" data-dt-page-length="10">
        <thead>
        <tr>
            <th data-col="name">Nama</th>
            <th data-col="email">Email</th>
            <th data-col="role">Role</th>
            <th data-col="is_active">Status</th>
            <th data-col="created_at">Dibuat</th>
            <th data-col="actions" data-orderable="false" data-searchable="false">Aksi</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
@endsection
