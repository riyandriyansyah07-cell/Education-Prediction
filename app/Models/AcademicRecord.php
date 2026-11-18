<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicRecord extends Model
{
    public const TRUST_SYSTEM = 1;

    public const TRUST_LECTURER_DPA = 2;

    public const TRUST_SELF_REPORTED = 3;

    public const SOURCE_MANDIRI = 'mandiri';

    public const SOURCE_OLU = 'olu';

    public const SOURCE_GDRIVE = 'gdrive';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'credits' => 'integer',
            'grade_point' => 'float',
            'attendance' => 'float',
            'uts_score' => 'float',
            'assignment_score' => 'float',
            'uas_score' => 'float',
            'assignment_recorded_at' => 'date',
            'projected_score' => 'float',
            'min_uas_for_c' => 'float',
            'min_uas_for_b' => 'float',
            'data_trust_level' => 'integer',
            'is_official_khs' => 'boolean',
        ];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_ref', 'student_ref');
    }

    /** Hitung berapa dari 4 komponen (Kehadiran, UTS, Tugas, UAS/KHS) yang sudah tersedia pada mata kuliah ini. */
    public function filledComponentsCount(): int
    {
        if ($this->is_official_khs && $this->grade_point !== null) {
            return 4;
        }

        $count = 1; // Kehadiran selalu terisi dari sistem akademik
        if ($this->uts_score !== null) {
            $count++;
        }
        if ($this->assignment_score !== null) {
            $count++;
        }
        if ($this->uas_score !== null) {
            $count++;
        }

        return $count;
    }

    public function trustLabel(): string
    {
        if ($this->data_trust_level === self::TRUST_LECTURER_DPA && $this->assignment_source) {
            return match ($this->assignment_source) {
                self::SOURCE_OLU => 'OLU (Terkonfirmasi dosen)',
                self::SOURCE_GDRIVE => 'GDrive (Pemeriksaan manual dosen)',
                default => 'Terkonfirmasi dosen/DPA',
            };
        }

        return self::trustLabelFor($this->data_trust_level);
    }

    public static function trustLabelFor(int $level): string
    {
        return match ($level) {
            self::TRUST_SYSTEM => 'Sistem akademik',
            self::TRUST_LECTURER_DPA => 'OLU, GDrive, atau DPA',
            self::TRUST_SELF_REPORTED => 'Dilaporkan sendiri (perkiraan)',
            default => 'Sistem akademik',
        };
    }
}
