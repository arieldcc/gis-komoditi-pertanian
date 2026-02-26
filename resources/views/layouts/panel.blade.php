@php
    $user = auth()->user();
    $menuItems = config('panel_menu.' . $user->role, []);
    $roleLabel = $user->roleLabel();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Panel' }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="panel-body"
    data-flash-success="{{ session('success') }}"
    data-flash-error="{{ session('error') }}"
    data-flash-validation-errors='@json($errors->all(), JSON_UNESCAPED_UNICODE)'
>
<div class="panel-shell">
    <aside class="panel-sidebar d-none d-lg-flex flex-column p-3">
        <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none panel-brand mb-3">
            <span class="navbar-brand-mark">GIS</span>
            <div>
                <div class="fw-semibold">SIG Komoditas</div>
                <small class="text-body-secondary">Banggai Kepulauan</small>
            </div>
        </a>

        <div class="panel-role-badge mb-3">
            <small class="text-body-secondary d-block">Role Login</small>
            <strong>{{ $roleLabel }}</strong>
        </div>

        @include('layouts.partials.sidebar-menu', ['menuItems' => $menuItems])

        <div class="mt-auto pt-3 border-top">
            <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary btn-sm w-100 mb-2">Profile</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm w-100">Logout</button>
            </form>
        </div>
    </aside>

    <div class="panel-main">
        <header class="panel-header border-bottom bg-white px-3 px-lg-4 py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-semibold">{{ $pageTitle ?? 'Dashboard' }}</h5>
                @if(!empty($pageSubtitle))
                    <small class="text-muted">{{ $pageSubtitle }}</small>
                @endif
            </div>

            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-success btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
                    Menu
                </button>
                <a href="{{ url('/') }}" class="btn btn-outline-secondary btn-sm">Lihat Landing</a>
            </div>
        </header>

        <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="mobileMenuLabel">Menu {{ $roleLabel }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column">
                @include('layouts.partials.sidebar-menu', ['menuItems' => $menuItems])
                <div class="mt-auto pt-3 border-top">
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary btn-sm w-100 mb-2">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">Logout</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="panel-content-scroll">
            <div class="panel-content-inner p-3 p-lg-4">
                @yield('panel_content')
            </div>
        </div>
    </div>
</div>
</body>
</html>
