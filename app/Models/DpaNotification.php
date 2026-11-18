<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DpaNotification extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'read_at' => 'datetime',
        ];
    }

    public function dpa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dpa_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_ref', 'student_ref');
    }

    public function riskResult(): BelongsTo
    {
        return $this->belongsTo(RiskResult::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
