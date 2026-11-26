<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkpiDocument extends Model
{
    public const TYPE_IT = 'it';
    public const TYPE_BAPS = 'baps';
    public const TYPE_PKM = 'pkm';
    public const TYPE_TOEFL = 'toefl';

    public const TYPES = [
        self::TYPE_IT,
        self::TYPE_BAPS,
        self::TYPE_PKM,
        self::TYPE_TOEFL,
    ];

    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_TERVERIFIKASI = 'terverifikasi';
    public const STATUS_DITOLAK = 'ditolak';

    protected $fillable = [
        'student_ref',
        'type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'verification_status',
        'verified_by',
        'verified_at',
        'verification_note',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_IT => 'Sertifikat Kompetensi IT',
            self::TYPE_BAPS => 'Baitul Arqam Purna Studi (BAPS)',
            self::TYPE_PKM => 'Sertifikat PKM',
            self::TYPE_TOEFL => 'Sertifikat TOEFL UHAMKA',
            default => strtoupper((string) $this->type),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->verification_status) {
            self::STATUS_TERVERIFIKASI => 'Terverifikasi',
            self::STATUS_DITOLAK => 'Perlu diperbaiki',
            default => 'Menunggu verifikasi',
        };
    }

    public function formattedSize(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.').' MB';
        }

        return max(1, (int) round($bytes / 1024)).' KB';
    }
}
