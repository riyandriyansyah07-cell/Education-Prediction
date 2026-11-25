<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'username', 'program_studi', 'email', 'role', 'password', 'student_ref', 'dpa_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_STUDENT = 'mahasiswa';

    public const ROLE_DPA = 'dpa';

    public const ROLE_ADMIN = 'admin';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'onboarding_seen_at' => 'datetime',
        ];
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function isDpa(): bool
    {
        return $this->role === self::ROLE_DPA;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_STUDENT => 'Mahasiswa',
            self::ROLE_DPA => 'Dosen pembimbing akademik',
            default => 'Admin sekretariat',
        };
    }

    /**
     * Ekstrak tahun angkatan (mis. 2023) dari 2 digit awal NIM mahasiswa (mis. 2303045009).
     */
    public function entryYear(): ?int
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $this->username);
        if (strlen($digits) < 6) {
            return null;
        }

        $year = 2000 + (int) substr($digits, 0, 2);

        return ($year >= 2015 && $year <= 2035) ? $year : null;
    }

    public function dpa(): BelongsTo
    {
        return $this->belongsTo(self::class, 'dpa_id');
    }

    public function advisees(): HasMany
    {
        return $this->hasMany(self::class, 'dpa_id');
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        return mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));
    }
}
