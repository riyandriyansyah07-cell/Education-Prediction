<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiValidation extends Model
{
    public const VERDICT_SESUAI = 'sesuai';

    public const VERDICT_KOREKSI = 'perlu_koreksi';

    public const VERDICTS = [
        self::VERDICT_SESUAI,
        self::VERDICT_KOREKSI,
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ai_score' => 'float',
            'rule_score' => 'float',
            'override_applied' => 'boolean',
            'validated_at' => 'datetime',
        ];
    }

    public function riskResult(): BelongsTo
    {
        return $this->belongsTo(RiskResult::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_id');
    }

    public function verdictLabel(): string
    {
        return match ($this->verdict) {
            self::VERDICT_SESUAI => 'Sesuai (Valid)',
            self::VERDICT_KOREKSI => 'Perlu Koreksi',
            default => 'Belum divalidasi',
        };
    }
}
