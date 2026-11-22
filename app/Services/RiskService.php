<?php

namespace App\Services;

use App\Mail\ReminderMail;
use App\Models\AcademicCalendar;
use App\Models\AcademicRecord;
use App\Models\Consent;
use App\Models\DpaNotification;
use App\Models\ImportBatch;
use App\Models\Reminder;
use App\Models\RiskResult;
use App\Models\RiskSnapshot;
use App\Models\StudentSetting;
use App\Models\User;
use App\Support\RiskModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RiskService
{
    public function __construct(
        private RiskEngine $engine,
        private ?LlmAdvisor $llm = null
    ) {
        $this->llm ??= app(LlmAdvisor::class);
    }

    /**
     * Hitung ulang risiko semua semester milik satu mahasiswa, perbarui proyeksi per mata kuliah,
     * simpan jejak risk_snapshots, buat pemberitahuan DPA bila naik ke Tinggi, dan kembalikan hasil terbaru.
     */
    public function refresh(string $ref, bool $force = false, ?string $phaseOverride = null): ?RiskResult
    {
        $bySemester = AcademicRecord::where('student_ref', $ref)->get()->groupBy('semester')->sortKeys();
        if ($bySemester->isEmpty()) {
            return null;
        }

        $batch = ImportBatch::where('student_ref', $ref)->latest('id')->first();
        $asOf = ($batch?->created_at ?? now())->toDateString();
        $settings = StudentSetting::firstOrCreate(['student_ref' => $ref]);

        $features = [];
        $courseProjectionsBySem = [];
        $previous = null;
        $allPriorSemesters = [];
        $lastSemesterKey = $bySemester->keys()->last();

        foreach ($bySemester as $semester => $records) {
            $courses = [];
            $semProjections = [];

            foreach ($records as $r) {
                $courseData = [
                    'id' => $r->id,
                    'course_code' => $r->course_code,
                    'course_name' => $r->course_name,
                    'credits' => (int) $r->credits,
                    'grade_point' => $r->grade_point !== null ? (float) $r->grade_point : null,
                    'grade_input' => $r->grade_input,
                    'attendance' => (float) $r->attendance,
                    'uts_score' => $r->uts_score !== null ? (float) $r->uts_score : null,
                    'assignment_score' => $r->assignment_score !== null ? (float) $r->assignment_score : null,
                    'uas_score' => $r->uas_score !== null ? (float) $r->uas_score : null,
                    'assignment_source' => $r->assignment_source,
                    'is_official_khs' => (bool) ($r->is_official_khs ?? ($r->grade_point !== null && $r->uts_score === null && $r->assignment_score === null && $r->uas_score === null)),
                    'dpa_confirmed_tasks' => (bool) $settings->dpa_confirmed_tasks,
                ];

                $proj = RiskModel::projectCourse($courseData);

                $r->update([
                    'projected_score' => $proj['projected_score'],
                    'min_uas_for_c' => $proj['min_uas_for_c'],
                    'min_uas_for_b' => $proj['min_uas_for_b'],
                    'course_risk_level' => $proj['course_risk_level'],
                    'data_trust_level' => $proj['data_trust_level'],
                    'is_official_khs' => $courseData['is_official_khs'],
                ]);

                $courses[] = $courseData;
                $semProjections[] = [
                    'course_code' => $r->course_code,
                    'course_name' => $r->course_name,
                    'credits' => (int) $r->credits,
                    'projected_score' => $proj['projected_score'],
                    'min_uas_for_c' => $proj['min_uas_for_c'],
                    'min_uas_for_b' => $proj['min_uas_for_b'],
                    'course_risk_level' => $proj['course_risk_level'],
                    'components_count' => $proj['components_count'],
                    'data_trust_level' => $proj['data_trust_level'],
                ];
            }

            $dpaInput = ($semester === $lastSemesterKey) ? [
                'activity_points' => $settings->activityRiskPoints(),
                'thesis_points' => $settings->thesisRiskPoints((int) $semester),
            ] : [];

            $features[$semester] = RiskModel::featuresFrom($courses, $previous, $allPriorSemesters, $dpaInput);
            $courseProjectionsBySem[$semester] = $semProjections;

            $previous = $courses;
            $allPriorSemesters[] = $courses;
        }

        // Kirim juga ringkasan proyeksi per mata kuliah ke RiskEngine agar Gemini dapat mengevaluasinya
        $assessments = $this->engine->assessMany(
            array_values($features),
            array_values($courseProjectionsBySem)
        );

        $latest = null;
        $previousLatestLevel = RiskResult::where('student_ref', $ref)
            ->where('semester', $lastSemesterKey)
            ->value('level');

        foreach (array_keys($features) as $i => $semester) {
            $assessment = $assessments[$i];
            $semesterFeatures = $features[$semester];
            if (! empty($assessment['explanation_unavailable'])) {
                $semesterFeatures['explanation_unavailable'] = true;
            }

            $isLatestSemester = ($semester === $lastSemesterKey);

            $gapFlagged = false;
            if (! ($semesterFeatures['is_projection'] ?? false)) {
                $lastProjectionSnap = RiskSnapshot::where('student_ref', $ref)
                    ->where('semester', $semester)
                    ->where('phase', '!=', RiskSnapshot::PHASE_KHS)
                    ->latest('id')
                    ->first();
                if ($lastProjectionSnap && abs((float) $lastProjectionSnap->score - (float) $assessment['score']) >= 2.0) {
                    $gapFlagged = true;
                }
            }

            // Jika RiskEngine (mode Gemini utama) sudah mengembalikan saran & faktor, langsung pakai tanpa panggil API 2 kali
            if (isset($assessment['suggestions']) && is_array($assessment['suggestions'])) {
                $suggestions = $assessment['suggestions'];
                $llmGenerated = (bool) ($assessment['llm_generated'] ?? false);
            } elseif ($isLatestSemester) {
                $advice = $this->llm->advise(
                    $assessment['level'],
                    (float) $assessment['score'],
                    $semesterFeatures,
                    $assessment['factors']
                );
                $suggestions = $advice['suggestions'];
                $llmGenerated = $advice['llm_generated'];
            } else {
                $suggestions = RiskModel::suggestions($assessment['level'], $assessment['factors']);
                $llmGenerated = false;
            }

            $semesterFeatures['encouragement'] = $assessment['encouragement']
                ?? LlmAdvisor::defaultEncouragement($assessment['level']);

            $latest = RiskResult::updateOrCreate(
                ['student_ref' => $ref, 'semester' => $semester],
                [
                    'level' => $assessment['level'],
                    'score' => $assessment['score'],
                    'components_count' => $semesterFeatures['components_count'] ?? 4,
                    'data_trust_level' => $semesterFeatures['data_trust_level'] ?? AcademicRecord::TRUST_SYSTEM,
                    'is_projection' => (bool) ($semesterFeatures['is_projection'] ?? false),
                    'projection_gap_flagged' => $gapFlagged,
                    'features' => $semesterFeatures,
                    'factors' => $assessment['factors'],
                    'suggestions' => $suggestions,
                    'llm_generated' => $llmGenerated,
                    'data_as_of' => $asOf,
                    'model_version_id' => $assessment['model_version_id'],
                ]
            );
        }

        if ($latest) {
            $this->recordSnapshot($ref, $latest, $courseProjectionsBySem[$latest->semester] ?? [], $phaseOverride);
            $this->notifyDpaIfEscalated($ref, $latest, $previousLatestLevel);

            if ($force || $latest->wasRecentlyCreated || $latest->wasChanged('level')) {
                $this->remind($ref, $latest, $force);
            }
        }

        return $latest;
    }

    private function recordSnapshot(string $ref, RiskResult $result, array $courseProjections, ?string $phaseOverride = null): void
    {
        $phase = $phaseOverride;
        if (! $phase) {
            if (! $result->is_projection) {
                $phase = RiskSnapshot::PHASE_KHS;
            } else {
                $calendar = AcademicCalendar::current();
                $phase = $calendar
                    ? $calendar->detectPhase()
                    : match ((int) $result->components_count) {
                        1 => RiskSnapshot::PHASE_AWAL,
                        2 => RiskSnapshot::PHASE_UTS,
                        3 => RiskSnapshot::PHASE_PRA_UAS,
                        default => RiskSnapshot::PHASE_KHS,
                    };
            }
        }

        RiskSnapshot::create([
            'student_ref' => $ref,
            'risk_result_id' => $result->id,
            'semester' => $result->semester,
            'phase' => $phase,
            'level' => $result->level,
            'score' => $result->score,
            'components_count' => $result->components_count ?? 4,
            'data_trust_level' => $result->data_trust_level ?? AcademicRecord::TRUST_SYSTEM,
            'features' => $result->features,
            'course_projections' => $courseProjections,
            'checked_at' => now(),
        ]);
    }

    private function notifyDpaIfEscalated(string $ref, RiskResult $latest, ?string $previousLevel): void
    {
        if ($latest->level !== 'tinggi') {
            return;
        }

        if ($previousLevel === null) {
            $previousLevel = RiskResult::where('student_ref', $ref)
                ->where('semester', '<', $latest->semester)
                ->orderByDesc('semester')
                ->value('level');
        }

        if ($previousLevel === 'tinggi') {
            return;
        }

        $student = User::where('student_ref', $ref)->first();
        if (! $student || ! $student->dpa_id) {
            return;
        }

        $fromText = $previousLevel ? "dari tingkat {$previousLevel} " : '';
        $message = "Mahasiswa bimbingan {$student->name} ({$student->username}) naik {$fromText}ke risiko Tinggi pada semester {$latest->semester}.";

        DpaNotification::firstOrCreate(
            [
                'dpa_id' => $student->dpa_id,
                'student_ref' => $ref,
                'semester' => $latest->semester,
                'current_level' => 'tinggi',
            ],
            [
                'risk_result_id' => $latest->id,
                'previous_level' => $previousLevel,
                'message' => $message,
            ]
        );
    }

    private function remind(string $ref, RiskResult $result, bool $force = false): void
    {
        if ($result->level === 'rendah') {
            return;
        }
        $settings = StudentSetting::firstOrCreate(['student_ref' => $ref]);

        $message = 'Hasil semester '.$result->semester.' menunjukkan risiko '.$result->level
            .'. Buka ringkasan risiko untuk melihat penyebab, proyeksi UAS minimal, dan saran langkahnya.';

        if ($settings->remind_in_app) {
            Reminder::firstOrCreate(
                ['student_ref' => $ref, 'risk_result_id' => $result->id, 'level' => $result->level],
                ['message' => $message]
            );
        }

        if ($settings->remind_email) {
            $hours = (int) config('mail.risk_throttle_hours', 24);
            $key = 'risk-email:'.$ref.':'.$result->level;
            $throttled = $force && $hours > 0;

            if ($throttled && Cache::has($key)) {
                Log::info('Pengingat email dilewati: sudah terkirim dalam '.$hours.' jam terakhir untuk '.$ref);

                return;
            }

            $user = User::where('student_ref', $ref)->first();
            if ($user?->email) {
                try {
                    Mail::to($user->email)->send(new ReminderMail($user, ReminderMail::KIND_RISK, $result));
                    if ($throttled) {
                        Cache::put($key, 1, now()->addHours($hours));
                    }
                    Log::info('Pengingat email terkirim ke '.$user->email);
                } catch (Throwable $e) {
                    Log::warning('Pengingat email gagal dikirim: '.$e->getMessage());
                }
            }
        }
    }
}