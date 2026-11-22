<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnrichmentRecord extends Model
{
    public const STATUS_DICICIL = 'dicicil';

    public const STATUS_SIAP = 'siap_rekognisi';

    public const STATUS_DIKONVERSI = 'dikonversi';

    public const STATUSES = [
        self::STATUS_DICICIL,
        self::STATUS_SIAP,
        self::STATUS_DIKONVERSI,
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'activity_semester' => 'integer',
            'conversion_semester' => 'integer',
        ];
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DIKONVERSI => 'Sudah dikonversi (KRS)',
            self::STATUS_SIAP => 'Bukti siap rekognisi',
            default => 'Sedang dicicil / berjalan',
        };
    }
}