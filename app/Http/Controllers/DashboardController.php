<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return match ($user->role) {
            User::ROLE_ADMIN_DINAS => redirect()->route('admin.dashboard'),
            User::ROLE_ADMIN_KECAMATAN => redirect()->route('kecamatan.dashboard'),
            User::ROLE_PENYULUH => redirect()->route('penyuluh.dashboard'),
            User::ROLE_PIMPINAN_DINAS => redirect()->route('pimpinan.dashboard'),
            default => redirect()->route('login'),
        };
    }
}
