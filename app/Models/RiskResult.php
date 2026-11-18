<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskResult extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'score' => 'float',
            'components_count' => 'integer',
            'data_trust_level' => 'integer',
            'is_projection' => 'boolean',
            'projection_gap_flagged' => 'boolean',
            'features' => 'array',
            'factors' => 'array',
            'suggestions' => 'array',
            'llm_generated' => 'boolean',
            'data_as_of' => 'date',
        ];
    }

    public function modelVersion(): BelongsTo
    {
        return $this->belongsTo(ModelVersion::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_ref', 'student_ref');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(RiskSnapshot::class)->orderByDesc('checked_at');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function trustLabel(): string
    {
        return AcademicRecord::trustLabelFor($this->data_trust_level ?? AcademicRecord::TRUST_SYSTEM);
    }

    /** Keterbukaan informasi jumlah komponen yang mendasari perhitungan. */
    public function completenessSummary(): string
    {
        $n = max(1, min(4, (int) ($this->components_count ?? 4)));

        return "Berdasarkan {$n} dari 4 komponen penilaian";
    }
}
