<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'SIG Komoditas') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container py-1">
        <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="{{ url('/') }}">
            <span class="navbar-brand-mark">GIS</span>
            <span>SIG Komoditas Pertanian</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                @auth
                    <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a></li>

                    @if(auth()->user()->hasRole('admin_dinas'))
                        <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}">Manajemen User</a></li>
                    @endif

                    <li class="nav-item">
                        <span class="badge text-bg-success-subtle text-success-emphasis border border-success-subtle">
                            {{ auth()->user()->roleLabel() }}
                        </span>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
                        </form>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#fitur">Fitur</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#komoditas">Komoditas</a></li>
                    <li class="nav-item"><a class="btn btn-success btn-sm px-3" href="{{ route('login') }}">Login Sistem</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<div class="container pt-3">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Ada data yang belum valid:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<main class="pb-5">
    @yield('content')
</main>
</body>
</html>
