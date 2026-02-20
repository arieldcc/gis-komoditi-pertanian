<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN_DINAS = 'admin_dinas';
    public const ROLE_ADMIN_KECAMATAN = 'admin_kecamatan';
    public const ROLE_PENYULUH = 'penyuluh';
    public const ROLE_PIMPINAN_DINAS = 'pimpinan_dinas';

    public const AVAILABLE_ROLES = [
        self::ROLE_ADMIN_DINAS,
        self::ROLE_ADMIN_KECAMATAN,
        self::ROLE_PENYULUH,
        self::ROLE_PIMPINAN_DINAS,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : explode(',', $roles);

        return in_array($this->role, array_map('trim', $roles), true);
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN_DINAS => 'Admin Dinas',
            self::ROLE_ADMIN_KECAMATAN => 'Admin Kecamatan',
            self::ROLE_PENYULUH => 'Penyuluh',
            self::ROLE_PIMPINAN_DINAS => 'Pimpinan Dinas',
            default => 'Tidak Diketahui',
        };
    }
}
