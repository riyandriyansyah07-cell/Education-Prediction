<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AcademicCalendar extends Model
{
    public const TERM_GASAL = 'gasal';

    public const TERM_GENAP = 'genap';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'uts_start_date' => 'date',
            'uts_end_date' => 'date',
            'uas_start_date' => 'date',
            'uas_end_date' => 'date',
            'khs_release_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current(): ?self
    {
        return self::where('is_current', true)->latest('id')->first();
    }

    /** Tentukan fase semester berjalan secara otomatis berdasarkan tanggal hari ini. */
    public function detectPhase(?Carbon $date = null): string
    {
        $now = ($date ?? now())->startOfDay();

        if ($this->khs_release_date && $now->greaterThanOrEqualTo($this->khs_release_date)) {
            return RiskSnapshot::PHASE_KHS;
        }
        if ($this->uas_start_date && $now->greaterThanOrEqualTo($this->uas_start_date->copy()->subDays(14))) {
            return RiskSnapshot::PHASE_PRA_UAS;
        }
        if ($this->uts_end_date && $now->greaterThan($this->uts_end_date->copy()->addDays(14))) {
            return RiskSnapshot::PHASE_TENGAH;
        }
        if ($this->uts_start_date && $now->greaterThanOrEqualTo($this->uts_start_date)) {
            return RiskSnapshot::PHASE_UTS;
        }

        return RiskSnapshot::PHASE_AWAL;
    }
}
