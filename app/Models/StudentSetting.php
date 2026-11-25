<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSetting extends Model
{
    public const STATUS_BELUM = 'belum_dihubungi';

    public const STATUS_DIJADWALKAN = 'dijadwalkan';

    public const STATUS_SUDAH = 'sudah_bimbingan';

    public const STATUS_SELESAI = 'selesai';

    public const ACTIVITY_AKTIF = 'aktif';

    public const ACTIVITY_CUKUP = 'cukup';

    public const ACTIVITY_KURANG = 'kurang';

    public const PKM_BELUM = 'belum';

    public const PKM_FAKULTAS = 'fakultas';

    public const PKM_UNIVERSITAS = 'universitas';

    public const PKM_PENDANAAN = 'pendanaan';

    public const PKM_STAGES = [
        self::PKM_BELUM,
        self::PKM_FAKULTAS,
        self::PKM_UNIVERSITAS,
        self::PKM_PENDANAAN,
    ];

    public const SKPI_BELUM = 'belum';

    public const SKPI_PROSES = 'proses';

    public const SKPI_SELESAI = 'selesai';

    public const BAPS_TERDAFTAR = 'terdaftar';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'remind_in_app' => 'boolean',
            'remind_email' => 'boolean',
            'last_guidance_at' => 'date',
            'dpa_confirmed_tasks' => 'boolean',
            'skpi_toefl_score' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_ref', 'student_ref');
    }

    public function updatedByDpa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dpa_updated_by');
    }

    public function followUpLabel(): string
    {
        return self::followUpLabelFor($this->follow_up_status);
    }

    public static function followUpLabelFor(?string $status): string
    {
        return match ($status) {
            self::STATUS_DIJADWALKAN => 'Dijadwalkan',
            self::STATUS_SUDAH => 'Sudah bimbingan',
            self::STATUS_SELESAI => 'Selesai',
            default => 'Belum dihubungi',
        };
    }

    public function activityLabel(): ?string
    {
        return self::activityLabelFor($this->dpa_activity_level);
    }

    public static function activityLabelFor(?string $level): ?string
    {
        return match ($level) {
            self::ACTIVITY_AKTIF => 'Aktif',
            self::ACTIVITY_CUKUP => 'Cukup',
            self::ACTIVITY_KURANG => 'Kurang',
            default => null,
        };
    }

    /** Poin risiko input DPA untuk keaktifan (Aman = 0, Waspada = 0.5, Kritis = 1.0), atau null bila belum diisi. */
    public function activityRiskPoints(): ?float
    {
        return match ($this->dpa_activity_level) {
            self::ACTIVITY_AKTIF => 0.0,
            self::ACTIVITY_CUKUP => 0.5,
            self::ACTIVITY_KURANG => 1.0,
            default => null,
        };
    }

    /** Poin risiko tugas akhir untuk mahasiswa semester 7 ke atas berdasarkan jarak hari bimbingan terakhir. */
    public function thesisRiskPoints(int $currentSemester): ?float
    {
        if ($currentSemester < 7 || ($this->thesis_status === null && $this->last_guidance_at === null)) {
            return null;
        }
        if ($this->thesis_status === 'belum_mulai' || $this->last_guidance_at === null) {
            return 1.0;
        }

        $days = (int) $this->last_guidance_at->diffInDays(now());
        if ($days <= 30) {
            return 0.0;
        }

        return $days <= 60 ? 0.5 : 1.0;
    }

    public static function pkmStageLabelFor(?string $stage): string
    {
        return match ($stage) {
            self::PKM_FAKULTAS => 'Lolos Tahap Fakultas (Belum SKPI)',
            self::PKM_UNIVERSITAS => 'Lolos Tahap Universitas (Sertifikat PKM / SKPI)',
            self::PKM_PENDANAAN => 'Lolos Pendanaan / PIMNAS (SKPI)',
            default => 'Belum Ikut PKM',
        };
    }
}
