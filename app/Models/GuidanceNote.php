<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuidanceNote extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'guidance_date' => 'date',
            'confirmed_course_scores' => 'boolean',
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

    public function followUpLabel(): string
    {
        return StudentSetting::followUpLabelFor($this->follow_up_status);
    }

    public function activityLabel(): ?string
    {
        return StudentSetting::activityLabelFor($this->activity_assessment);
    }
}
