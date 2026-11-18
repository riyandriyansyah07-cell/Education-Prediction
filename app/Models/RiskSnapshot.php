<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskSnapshot extends Model
{
    public const PHASE_AWAL = 'awal_semester';

    public const PHASE_UTS = 'setelah_uts';

    public const PHASE_TENGAH = 'tengah_semester';

    public const PHASE_PRA_UAS = 'sebelum_uas';

    public const PHASE_KHS = 'khs_terbit';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'score' => 'float',
            'components_count' => 'integer',
            'data_trust_level' => 'integer',
            'features' => 'array',
            'course_projections' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function riskResult(): BelongsTo
    {
        return $this->belongsTo(RiskResult::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_ref', 'student_ref');
    }

    public function phaseLabel(): string
    {
        return match ($this->phase) {
            self::PHASE_AWAL => 'Awal semester (sebelum UTS)',
            self::PHASE_UTS => 'Setelah UTS',
            self::PHASE_TENGAH => 'Pertengahan semester',
            self::PHASE_PRA_UAS => 'Menjelang UAS',
            self::PHASE_KHS => 'Setelah KHS resmi terbit',
            default => $this->phase,
        };
    }

    public function trustLabel(): string
    {
        return AcademicRecord::trustLabelFor($this->data_trust_level);
    }
}
