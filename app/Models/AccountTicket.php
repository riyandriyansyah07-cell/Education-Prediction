<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountTicket extends Model
{
    public const PROGRAMS = [
        'Teknik Informatika',
        'Teknik Elektro',
        'Teknik Mesin',
        'Sistem dan Teknologi Informasi',
        'Teknologi Rekayasa Mekatronika',
    ];

    public const TYPE_STUDENT = 'akun_mahasiswa';

    public const TYPE_DPA = 'akun_dpa';

    public const TYPE_RESET = 'reset_sandi';

    public const STATUS_PENDING = 'menunggu';

    public const STATUS_APPROVED = 'disetujui';

    public const STATUS_REJECTED = 'ditolak';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function typeLabel(): string
    {
        return match ($this->request_type) {
            self::TYPE_DPA => 'Akun Baru DPA',
            self::TYPE_RESET => 'Reset Kata Sandi',
            default => 'Akun Baru Mahasiswa',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Menunggu verifikasi',
        };
    }
}