@extends('layouts.panel', [
    'pageTitle' => 'Tambah User',
    'pageSubtitle' => 'Input akun baru dan tetapkan role akses.',
])

@section('panel_content')
<div class="form-wrapper p-4">
    <form method="POST" action="{{ route('admin.users.store') }}" class="row g-3">
        @csrf
        @include('admin.users.partials.form-fields', ['user' => null])
    </form>
</div>
@endsection
