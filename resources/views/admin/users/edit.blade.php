@extends('layouts.panel', [
    'pageTitle' => 'Edit User',
    'pageSubtitle' => 'Perbarui informasi akun pengguna.',
])

@section('panel_content')
<div class="form-wrapper p-4">
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="row g-3">
        @csrf
        @method('PUT')
        @include('admin.users.partials.form-fields', ['user' => $user])
    </form>
</div>
@endsection
