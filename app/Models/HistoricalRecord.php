<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalRecord extends Model
{
    public const CORE_FEATURES = [
        'attendance',
        'min_attendance',
        'low_attendance_courses',
        'attendance_delta',
        'ips',
        'ipk',
        'ips_delta',
        'failed_ratio',
        'repeat_courses',
        'credits_delta',
    ];

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'attendance' => 'float',
            'min_attendance' => 'float',
            'low_attendance_courses' => 'integer',
            'attendance_delta' => 'float',
            'ips' => 'float',
            'ipk' => 'float',
            'ips_delta' => 'float',
            'failed_ratio' => 'float',
            'repeat_courses' => 'integer',
            'credits_delta' => 'integer',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(HistoricalDataset::class, 'historical_dataset_id');
    }

    /** Format 10 fitur inti + label untuk pelatihan di ModelService dan ml-service. */
    public function toTrainingRow(): array
    {
        return [
            'attendance' => (float) $this->attendance,
            'min_attendance' => (float) $this->min_attendance,
            'low_attendance_courses' => (int) $this->low_attendance_courses,
            'attendance_delta' => (float) $this->attendance_delta,
            'ips' => (float) $this->ips,
            'ipk' => (float) $this->ipk,
            'ips_delta' => (float) $this->ips_delta,
            'failed_ratio' => (float) $this->failed_ratio,
            'repeat_courses' => (int) $this->repeat_courses,
            'credits_delta' => (int) $this->credits_delta,
            'label' => $this->label,
        ];
    }
}
