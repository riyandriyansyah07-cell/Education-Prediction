<?php

namespace App\Support;

use App\Models\AcademicRecord;

/**
 * Model risiko berbasis proyeksi nilai akhir dan 10 variabel inti:
 * - Proyeksi per mata kuliah: UAS 40%, UTS 25%, Tugas 25%, Kehadiran/Keaktifan 10%
 * - Rumus nilai sementara (N) dengan pembagian ulang bobot komponen yang aktif
 * - Rumus nilai UAS minimal (U_min) untuk target C (56) dan B (68)
 * - Penggabungan skor mahasiswa (0-10): Semester berjalan 70%, Riwayat 20%, Input DPA 10%
 * - Tambahan faktor progres wajib 20 SKS MK Pengayaan khusus semester 5 ke atas
 */
final class RiskModel
{
    public const LEVELS = ['rendah', 'sedang', 'tinggi'];

    public const DEFAULT_THRESHOLDS = ['sedang' => 2.5, 'tinggi' => 5.0];

    public const MAX_SCORE = 10.0;

    /** Batas minimal kehadiran UHAMKA untuk mengikuti ujian (75%). */
    public const MIN_ATTENDANCE_LIMIT = 75.0;

    /** Daftar 10 variabel inti yang seragam di seluruh mata kuliah & data historis. */
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

    /** Bobot standar komponen penilaian per mata kuliah. */
    public const COURSE_WEIGHTS = [
        'uas' => 0.40,
        'uts' => 0.25,
        'assignment' => 0.25,
        'attendance' => 0.10,
    ];

    /** Bobot standar bagian skor risiko tingkat mahasiswa. */
    public const STUDENT_WEIGHTS = [
        'current_semester' => 0.70,
        'history' => 0.20,
        'dpa' => 0.10,
    ];

    /**
     * Hitung proyeksi nilai akhir (N), nilai UAS minimal (U_min), dan tingkat risiko untuk satu mata kuliah.
     *
     * @param  array{
     *   credits?:int,
     *   attendance:float,
     *   grade_point?:float|null,
     *   grade_input?:string|null,
     *   uts_score?:float|null,
     *   assignment_score?:float|null,
     *   uas_score?:float|null,
     *   assignment_source?:string|null,
     *   is_official_khs?:bool,
     *   dpa_confirmed_tasks?:bool
     * }  $course
     * @return array{
     *   projected_score:float,
     *   min_uas_for_c:float|null,
     *   min_uas_for_b:float|null,
     *   course_risk_level:string,
     *   risk_points:float,
     *   components_count:int,
     *   data_trust_level:int
     * }
     */
    public static function projectCourse(array $course): array
    {
        $attendance = max(0.0, min(100.0, (float) ($course['attendance'] ?? 0.0)));
        $uts = isset($course['uts_score']) && $course['uts_score'] !== null ? (float) $course['uts_score'] : null;
        $assignment = isset($course['assignment_score']) && $course['assignment_score'] !== null ? (float) $course['assignment_score'] : null;
        $uas = isset($course['uas_score']) && $course['uas_score'] !== null ? (float) $course['uas_score'] : null;
        $gradePoint = isset($course['grade_point']) && $course['grade_point'] !== null ? (float) $course['grade_point'] : null;
        $isOfficial = (bool) ($course['is_official_khs'] ?? ($gradePoint !== null && $uts === null && $assignment === null && $uas === null));

        // 1. Jika KHS resmi sudah terbit dan nilai akhir tersedia
        if ($isOfficial && $gradePoint !== null) {
            $finalScore = Grades::pointToScore100($gradePoint, $course['grade_input'] ?? null);
            $level = self::classifyCourseFinal($finalScore, $attendance);

            return [
                'projected_score' => $finalScore,
                'min_uas_for_c' => null,
                'min_uas_for_b' => null,
                'course_risk_level' => $level,
                'risk_points' => self::levelPoints($level),
                'components_count' => 4,
                'data_trust_level' => AcademicRecord::TRUST_SYSTEM,
            ];
        }

        // 2. Hitung nilai sementara N = (\sum w_i s_i) / (\sum w_i) dari komponen yang sudah terisi
        $weightedSum = self::COURSE_WEIGHTS['attendance'] * $attendance;
        $totalWeight = self::COURSE_WEIGHTS['attendance'];
        $componentsCount = 1;

        if ($uts !== null) {
            $weightedSum += self::COURSE_WEIGHTS['uts'] * max(0.0, min(100.0, $uts));
            $totalWeight += self::COURSE_WEIGHTS['uts'];
            $componentsCount++;
        }

        if ($assignment !== null) {
            $weightedSum += self::COURSE_WEIGHTS['assignment'] * max(0.0, min(100.0, $assignment));
            $totalWeight += self::COURSE_WEIGHTS['assignment'];
            $componentsCount++;
        }

        if ($uas !== null) {
            $weightedSum += self::COURSE_WEIGHTS['uas'] * max(0.0, min(100.0, $uas));
            $totalWeight += self::COURSE_WEIGHTS['uas'];
            $componentsCount++;
        }

        $projectedScore = round($weightedSum / max(0.01, $totalWeight), 2);

        // 3. Hitung UAS minimal (U_min) bila UAS belum diisi
        // Jika tugas kosong (dilewati), bobot pra-UAS yang aktif dinormalisasi ke porsi non-UAS (0,60)
        $minUasC = null;
        $minUasB = null;
        if ($uas === null) {
            $preUasWeight = $totalWeight; // total bobot komponen non-UAS yang terisi
            $nonUasShare = 1.0 - self::COURSE_WEIGHTS['uas']; // 0.60
            $earnedPreUas = $preUasWeight > 0 ? ($weightedSum / $preUasWeight) * $nonUasShare : 0.0;

            $minUasC = round(max(0.0, (Grades::THRESHOLD_C - $earnedPreUas) / self::COURSE_WEIGHTS['uas']), 2);
            $minUasB = round(max(0.0, (Grades::THRESHOLD_B - $earnedPreUas) / self::COURSE_WEIGHTS['uas']), 2);
        }

        // 4. Tentukan tingkat risiko mata kuliah
        if ($attendance < self::MIN_ATTENDANCE_LIMIT) {
            // Kehadiran di bawah 75% langsung dianggap Tinggi karena menghalangi ikut ujian
            $level = 'tinggi';
        } elseif ($uas !== null) {
            $level = self::classifyCourseFinal($projectedScore, $attendance);
        } elseif ($uts !== null || $assignment !== null) {
            // Sebelum UAS: menggunakan batas UAS minimal untuk mencapai C (T = 56)
            if ($minUasC > 70.0) {
                $level = 'tinggi';
            } elseif ($minUasC >= 40.0) {
                $level = 'sedang';
            } else {
                $level = 'rendah';
            }
        } else {
            // Awal semester (baru ada kehadiran)
            $level = $attendance >= 85.0 ? 'rendah' : 'sedang';
        }

        // 5. Tentukan tingkat kepercayaan data (1: Sistem, 2: Dosen/DPA, 3: Dilaporkan sendiri)
        $trustLevel = AcademicRecord::TRUST_SYSTEM;
        $source = $course['assignment_source'] ?? null;
        $dpaConfirmed = (bool) ($course['dpa_confirmed_tasks'] ?? false);

        if ($uts !== null || $uas !== null || ($assignment !== null && $source === AcademicRecord::SOURCE_MANDIRI && ! $dpaConfirmed)) {
            $trustLevel = AcademicRecord::TRUST_SELF_REPORTED;
        } elseif ($assignment !== null && (in_array($source, [AcademicRecord::SOURCE_OLU, AcademicRecord::SOURCE_GDRIVE], true) || $dpaConfirmed)) {
            $trustLevel = AcademicRecord::TRUST_LECTURER_DPA;
        }

        return [
            'projected_score' => $projectedScore,
            'min_uas_for_c' => $minUasC,
            'min_uas_for_b' => $minUasB,
            'course_risk_level' => $level,
            'risk_points' => self::levelPoints($level),
            'components_count' => $componentsCount,
            'data_trust_level' => $trustLevel,
        ];
    }

    /**
     * Ekstrak 10 variabel inti beserta metrik proyeksi semester dari daftar mata kuliah.
     *
     * @param  list<array>  $courses  Mata kuliah semester ini
     * @param  list<array>|null  $previousCourses  Mata kuliah semester sebelumnya (bila ada)
     * @param  list<list<array>>  $allPriorSemesters  Seluruh semester sebelumnya untuk perhitungan IPK kumulatif
     * @param  array{activity_points?:float|null,thesis_points?:float|null}  $dpaInput  Input penilaian DPA (bila ada)
     */
    public static function featuresFrom(
        array $courses,
        ?array $previousCourses = null,
        array $allPriorSemesters = [],
        array $dpaInput = []
    ): array {
        $credits = max(1, (int) array_sum(array_column($courses, 'credits')));

        $weightedAttendance = 0.0;
        $minAttendance = 100.0;
        $lowAttendanceCourses = 0;
        $failedCredits = 0;
        $repeatCourses = 0;
        $weightedRiskPoints = 0.0;
        $totalComponents = 0;
        $maxTrustLevel = AcademicRecord::TRUST_SYSTEM;
        $allOfficial = true;

        foreach ($courses as $c) {
            $sks = max(1, (int) ($c['credits'] ?? 1));
            $att = (float) ($c['attendance'] ?? 0.0);
            $projection = self::projectCourse($c);

            $weightedAttendance += $att * $sks;
            if ($att < $minAttendance) {
                $minAttendance = $att;
            }
            if ($att < self::MIN_ATTENDANCE_LIMIT) {
                $lowAttendanceCourses++;
            }

            // Gunakan grade_point resmi bila ada, atau konversi dari projected_score
            $effectivePoint = isset($c['grade_point']) && $c['grade_point'] !== null
                ? (float) $c['grade_point']
                : (Grades::toPoint((string) $projection['projected_score'], 'score100') ?? 0.0);

            if ($effectivePoint < 2.0) {
                $failedCredits += $sks;
            }
            if ($effectivePoint <= 1.0) {
                $repeatCourses++;
            }

            $weightedRiskPoints += $projection['risk_points'] * $sks;
            $totalComponents += $projection['components_count'];
            if ($projection['data_trust_level'] > $maxTrustLevel) {
                $maxTrustLevel = $projection['data_trust_level'];
            }
            if (! ($c['is_official_khs'] ?? ($c['grade_point'] ?? null) !== null)) {
                $allOfficial = false;
            }
        }

        $avgAttendance = round($weightedAttendance / $credits, 2);
        $ips = round(self::ips($courses), 2);
        $prevIps = $previousCourses ? round(self::ips($previousCourses), 2) : $ips;
        $prevAttendance = $previousCourses ? round(self::avgAttendance($previousCourses), 2) : $avgAttendance;
        $prevCredits = $previousCourses ? (int) array_sum(array_column($previousCourses, 'credits')) : $credits;

        // Hitung IPK kumulatif di seluruh semester yang pernah ditempuh
        $allCourses = $courses;
        if ($allPriorSemesters !== []) {
            foreach ($allPriorSemesters as $semCourses) {
                $allCourses = array_merge($allCourses, $semCourses);
            }
        } elseif ($previousCourses !== null) {
            $allCourses = array_merge($previousCourses, $courses);
        }
        $ipk = round(self::ips($allCourses), 2);

        $courseCount = max(1, count($courses));
        $avgComponents = (int) round($totalComponents / $courseCount);

        return [
            // 10 Variabel Inti:
            'attendance' => $avgAttendance,
            'min_attendance' => round($courses === [] ? 0.0 : $minAttendance, 2),
            'low_attendance_courses' => $lowAttendanceCourses,
            'attendance_delta' => $previousCourses ? round($avgAttendance - $prevAttendance, 2) : 0.0,
            'ips' => $ips,
            'ipk' => $ipk,
            'ips_delta' => $previousCourses ? round($ips - $prevIps, 2) : 0.0,
            'failed_ratio' => round($failedCredits / $credits, 4),
            'repeat_courses' => $repeatCourses,
            'credits_delta' => $previousCourses ? ($credits - $prevCredits) : 0,

            // Metadata agregasi semester berjalan & DPA:
            'credits' => $credits,
            'current_semester_points' => round($weightedRiskPoints / $credits, 4),
            'components_count' => max(1, min(4, $avgComponents)),
            'data_trust_level' => $maxTrustLevel,
            'is_projection' => ! $allOfficial,
            'dpa_activity_points' => $dpaInput['activity_points'] ?? null,
            'dpa_thesis_points' => $dpaInput['thesis_points'] ?? null,
        ];
    }

    /**
     * Hitung kontribusi poin tiap faktor menuju skor 0-10.
     * Menggabungkan bagian Semester Berjalan (70%), Riwayat (20%), Input DPA (10%),
     * serta faktor keterlambatan SKS Pengayaan (khusus semester 5+).
     */
    public static function parts(array $f): array
    {
        $attendance = (float) ($f['attendance'] ?? 85.0);
        $minAttendance = (float) ($f['min_attendance'] ?? $attendance);
        $lowAttCount = (int) ($f['low_attendance_courses'] ?? ($attendance < self::MIN_ATTENDANCE_LIMIT ? 1 : 0));
        $attDelta = (float) ($f['attendance_delta'] ?? 0.0);
        $ips = (float) ($f['ips'] ?? 3.0);
        $ipk = (float) ($f['ipk'] ?? $ips);
        $ipsDelta = (float) ($f['ips_delta'] ?? 0.0);
        $failedRatio = (float) ($f['failed_ratio'] ?? 0.0);
        $repeatCourses = (int) ($f['repeat_courses'] ?? ($failedRatio >= 0.25 ? 2 : ($failedRatio > 0 ? 1 : 0)));
        $creditsDelta = (int) ($f['credits_delta'] ?? 0);
        $enrichmentPenalty = max(0.0, min(1.5, (float) ($f['enrichment_risk_points'] ?? 0.0)));

        // 1. Poin Semester Berjalan (0.0 s.d. 1.0)
        if (isset($f['current_semester_points']) && is_numeric($f['current_semester_points'])) {
            $currentNorm = max(0.0, min(1.0, (float) $f['current_semester_points']));
        } else {
            // Estimasi dari fitur inti bila dipanggil pada baris data historis tanpa rincian MK
            $ipsRisk = $ips < 2.0 ? 1.0 : ($ips < 2.75 ? 0.5 : 0.0);
            $attRisk = $attendance < 75.0 ? 1.0 : ($attendance < 85.0 ? 0.5 : 0.0);
            $failRisk = min(1.0, $failedRatio * 2.5);
            $currentNorm = round(0.45 * $ipsRisk + 0.30 * $attRisk + 0.25 * $failRisk, 4);
        }

        // 2. Poin Riwayat berdasarkan tabel band (Aman = 0, Waspada = 0.5, Kritis = 1.0)
        $ipkBand = $ipk >= 3.00 ? 0.0 : ($ipk >= 2.00 ? 0.5 : 1.0);
        $trendBand = $ipsDelta >= 0.0 ? 0.0 : ($ipsDelta > -0.50 ? 0.5 : 1.0);
        $repeatBand = $repeatCourses <= 0 ? 0.0 : ($repeatCourses === 1 ? 0.5 : 1.0);
        $creditsBand = $creditsDelta <= 3 ? 0.0 : ($creditsDelta <= 6 ? 0.5 : 1.0);
        $historyNorm = ($ipkBand + $trendBand + $repeatBand + $creditsBand) / 4.0;

        // 3. Poin Input DPA (lewati bila kosong)
        $dpaValues = array_values(array_filter([
            $f['dpa_activity_points'] ?? null,
            $f['dpa_thesis_points'] ?? null,
        ], fn ($v) => $v !== null && is_numeric($v)));
        $dpaNorm = $dpaValues !== [] ? array_sum($dpaValues) / count($dpaValues) : null;

        // 4. Pembagian ulang bobot bagian (Semester berjalan, Riwayat, DPA)
        $wCurrent = self::STUDENT_WEIGHTS['current_semester'];
        $wHistory = self::STUDENT_WEIGHTS['history'];
        $wDpa = $dpaNorm !== null ? self::STUDENT_WEIGHTS['dpa'] : 0.0;

        // Proteksi bias awal semester: bila baru ada 1 komponen (hanya kehadiran, belum UTS),
        // seimbangkan bobot ke riwayat agar mahasiswa ber-IPK kritis tidak tampak "Rendah" palsu
        $componentsCount = (int) ($f['components_count'] ?? 4);
        if ($componentsCount <= 1 && ! empty($f['is_projection'])) {
            $wCurrent = 0.40;
            $wHistory = 0.50;
        }

        $weightSum = max(0.01, $wCurrent + $wHistory + $wDpa);
        $totalScore = 10.0 * (
            ($wCurrent * $currentNorm) +
            ($wHistory * $historyNorm) +
            ($wDpa * ($dpaNorm ?? 0.0))
        ) / $weightSum;

        // Tambahkan penalti keterlambatan 20 SKS MK Pengayaan (hanya aktif mulai semester 5+)
        if ($enrichmentPenalty > 0.0) {
            $totalScore = min(self::MAX_SCORE, $totalScore + $enrichmentPenalty);
        }

        // Guardrail aturan akademik UHAMKA: kehadiran di bawah 75% tidak boleh masuk kategori Rendah
        if ($lowAttCount > 0 || $minAttendance < self::MIN_ATTENDANCE_LIMIT) {
            $totalScore = max($totalScore, 3.0);
        }

        // 5. Distribusi ke tiap faktor agar jumlah poin faktor sama dengan skor total (aditif)
        $rawParts = [
            'attendance' => max(0.0, (85.0 - $attendance) * 0.12) + ($minAttendance < 75.0 ? 0.8 : 0.0),
            'low_attendance_courses' => $lowAttCount * 0.75,
            'attendance_delta' => $attDelta < -5.0 ? min(1.5, abs($attDelta) * 0.08) : 0.0,
            'ips' => max(0.0, (2.75 - $ips) * 1.8),
            'ipk' => $ipkBand * 1.5,
            'ips_delta' => $trendBand * 1.4,
            'failed_ratio' => $failedRatio * 6.0,
            'repeat_courses' => $repeatBand * 1.5,
            'credits_delta' => $creditsBand * 1.0,
        ];
        if ($dpaNorm !== null && $dpaNorm > 0) {
            $rawParts['dpa_assessment'] = $dpaNorm * 1.5;
        }
        if ($enrichmentPenalty > 0.0) {
            $rawParts['enrichment_sks'] = $enrichmentPenalty;
        }

        $rawSum = array_sum($rawParts);
        if ($rawSum <= 0.0 || $totalScore <= 0.0) {
            return array_map(fn () => 0.0, $rawParts);
        }

        $scaled = [];
        foreach ($rawParts as $k => $v) {
            $scaled[$k] = round(($v / $rawSum) * $totalScore, 2);
        }

        return $scaled;
    }

    public static function score(array $f): float
    {
        return round(min(self::MAX_SCORE, max(0.0, array_sum(self::parts($f)))), 2);
    }

    /** @param array{sedang:float,tinggi:float} $t */
    public static function classify(float $score, array $t = self::DEFAULT_THRESHOLDS): string
    {
        if ($score >= ($t['tinggi'] ?? self::DEFAULT_THRESHOLDS['tinggi'])) {
            return 'tinggi';
        }

        return $score >= ($t['sedang'] ?? self::DEFAULT_THRESHOLDS['sedang']) ? 'sedang' : 'rendah';
    }

    /**
     * Faktor penyebab model aturan, diurutkan dari kontribusi terbesar.
     *
     * @return list<array{key:string,label:string,detail:string,points:float,share:float}>
     */
    public static function factors(array $f): array
    {
        return self::explain($f, self::parts($f));
    }

    /**
     * Susun faktor penyebab dari poin per faktor (dipakai baik oleh model aturan maupun nilai SHAP model ML).
     *
     * @param  array<string,float>  $points
     * @return list<array{key:string,label:string,detail:string,points:float,share:float}>
     */
    public static function explain(array $f, array $points): array
    {
        // Kompatibilitas kunci faktor lama ('failed' -> 'failed_ratio', 'trend' -> 'ips_delta')
        if (isset($points['failed']) && ! isset($points['failed_ratio'])) {
            $points['failed_ratio'] = $points['failed'];
        }
        if (isset($points['trend']) && ! isset($points['ips_delta'])) {
            $points['ips_delta'] = $points['trend'];
        }

        $labels = [
            'attendance' => 'Kehadiran rata-rata kuliah',
            'min_attendance' => 'Kehadiran terendah mata kuliah',
            'low_attendance_courses' => 'Mata kuliah di bawah batas kehadiran 75%',
            'attendance_delta' => 'Tren kehadiran dari semester lalu',
            'ips' => 'Indeks prestasi semester (IPS)',
            'ipk' => 'Indeks prestasi kumulatif (IPK)',
            'ips_delta' => 'Tren perubahan IPS',
            'failed_ratio' => 'Rasio SKS bernilai di bawah C',
            'repeat_courses' => 'Mata kuliah mengulang atau bernilai D/E',
            'credits_delta' => 'Perubahan beban SKS',
            'dpa_assessment' => 'Pemantauan keaktifan & tugas akhir (DPA)',
            'enrichment_sks' => 'Progres wajib 20 SKS MK Pengayaan',
        ];

        $att = (float) ($f['attendance'] ?? 0.0);
        $minAtt = (float) ($f['min_attendance'] ?? $att);
        $lowAttCount = (int) ($f['low_attendance_courses'] ?? 0);
        $attDelta = round((float) ($f['attendance_delta'] ?? 0.0), 2);
        $ips = (float) ($f['ips'] ?? 0.0);
        $ipk = (float) ($f['ipk'] ?? $ips);
        $ipsDelta = round((float) ($f['ips_delta'] ?? 0.0), 2);
        $failedRatio = (float) ($f['failed_ratio'] ?? 0.0);
        $repeatCourses = (int) ($f['repeat_courses'] ?? 0);
        $creditsDelta = (int) ($f['credits_delta'] ?? 0);
        $enrConverted = (int) ($f['enrichment_converted_sks'] ?? 0);
        $enrReady = (int) ($f['enrichment_ready_sks'] ?? 0);
        $enrSem = (int) ($f['enrichment_semester'] ?? 1);

        $details = [
            'attendance' => $att < 85.0
                ? 'Kehadiran rata-rata tertimbang '.Fmt::pct($att).', di bawah batas aman 85%.'
                : 'Kehadiran rata-rata tertimbang '.Fmt::pct($att).' (ikut berkontribusi pada pola model).',
            'min_attendance' => $minAtt < self::MIN_ATTENDANCE_LIMIT
                ? 'Ada mata kuliah dengan kehadiran '.Fmt::pct($minAtt).', di bawah syarat ujian 75%.'
                : 'Kehadiran mata kuliah terendah berada pada '.Fmt::pct($minAtt).'.',
            'low_attendance_courses' => $lowAttCount > 0
                ? "Terdapat {$lowAttCount} mata kuliah dengan kehadiran di bawah batas minimum 75%."
                : 'Seluruh mata kuliah memenuhi syarat kehadiran minimum 75%.',
            'attendance_delta' => $attDelta < 0
                ? 'Kehadiran rata-rata turun '.Fmt::num(abs($attDelta)).' poin persen dibanding semester lalu.'
                : 'Kehadiran rata-rata berubah '.Fmt::num($attDelta).' poin persen dari semester lalu.',
            'ips' => $ips < 2.75
                ? 'IPS semester berjalan '.Fmt::num($ips).', di bawah batas aman 2,75.'
                : 'IPS semester berjalan '.Fmt::num($ips).' (ikut berkontribusi pada perhitungan).',
            'ipk' => $ipk < 3.00
                ? 'IPK kumulatif saat ini '.Fmt::num($ipk).' (kategori waspada/kritis di bawah 3,00).'
                : 'IPK kumulatif saat ini '.Fmt::num($ipk).'.',
            'ips_delta' => $ipsDelta < 0.0
                ? 'IPS turun '.Fmt::num(abs($ipsDelta)).' poin dibandingkan semester sebelumnya.'
                : 'Perubahan IPS sebesar '.Fmt::num($ipsDelta).' poin dari semester sebelumnya.',
            'failed_ratio' => $failedRatio > 0.0
                ? Fmt::pct($failedRatio * 100).' dari total SKS bernilai di bawah C (kurang dari 56).'
                : 'Tidak ada SKS bernilai di bawah C (0%).',
            'repeat_courses' => $repeatCourses > 0
                ? "Terdapat {$repeatCourses} mata kuliah bernilai D/E yang perlu diulang."
                : 'Tidak ada mata kuliah bernilai D atau E.',
            'credits_delta' => $creditsDelta > 3
                ? "Beban studi naik {$creditsDelta} SKS dibandingkan semester sebelumnya."
                : "Perubahan beban studi sebesar {$creditsDelta} SKS.",
            'dpa_assessment' => 'Catatan bimbingan DPA menunjukkan perlunya perhatian pada keaktifan atau progres tugas akhir.',
            'enrichment_sks' => "Di semester {$enrSem}, progres MK Pengayaan baru {$enrConverted} SKS terkonversi KRS dan {$enrReady} SKS bukti siap rekognisi dari target wajib 20 SKS.",
        ];

        $positive = array_filter(
            array_map(fn ($v) => round((float) $v, 2), array_intersect_key($points, $labels)),
            fn ($v) => $v > 0.0
        );
        $total = array_sum($positive);

        if ($total <= 0.0) {
            return [];
        }

        $out = [];
        foreach ($positive as $key => $value) {
            $out[] = [
                'key' => $key,
                'label' => $labels[$key],
                'detail' => $details[$key],
                'points' => $value,
                'share' => round(($value / $total) * 100, 1),
            ];
        }
        usort($out, fn ($a, $b) => $b['points'] <=> $a['points']);

        return $out;
    }

    /**
     * Saran tindak lanjut statis (cadangan otomatis bila layanan LLM tidak aktif atau gagal).
     *
     * @return list<string>
     */
    public static function suggestions(string $level, array $factors): array
    {
        $map = [
            'attendance' => 'Cek jadwal kuliah yang paling sering terlewat dan catat penyebabnya agar kehadiran tetap terjaga di atas 85%.',
            'min_attendance' => 'Prioritaskan hadir penuh pada mata kuliah yang kehadirannya mendekati atau di bawah batas 75% agar tetap berhak mengikuti UAS.',
            'low_attendance_courses' => 'Segera hubungi dosen pengampu pada mata kuliah yang kehadirannya di bawah 75% untuk mengonfirmasi syarat mengikuti ujian akhir.',
            'attendance_delta' => 'Evaluasi perubahan rutinitas semester ini yang membuat kehadiran menurun dibanding semester lalu.',
            'ips' => 'Fokuskan target belajar mingguan pada satu atau dua mata kuliah ber-SKS besar yang nilai proyeksinya masih di bawah 68 (B).',
            'ipk' => 'Susun strategi perbaikan nilai bersama DPA agar IPK kumulatif dapat kembali melampaui batas aman 3,00.',
            'ips_delta' => 'Tinjau kembali metode belajar semester ini karena tren IPS menurun dibandingkan semester sebelumnya.',
            'failed_ratio' => 'Cek daftar nilai UAS minimal (U_min) pada tiap mata kuliah dan kejar target nilai di atas 56 (C) agar tidak ada SKS yang gagal.',
            'repeat_courses' => 'Rencanakan pengulangan mata kuliah bernilai D/E secara bertahap tanpa membebani semester berjalan secara berlebihan.',
            'credits_delta' => 'Karena beban SKS semester ini meningkat, buat jadwal pengerjaan tugas dua mingguan agar tidak menumpuk menjelang UAS.',
            'dpa_assessment' => 'Jadwalkan pertemuan tindak lanjut dengan DPA untuk memperbarui progres keaktifan kuliah dan bimbingan tugas akhir.',
            'enrichment_sks' => 'Buka menu SKS Pengayaan dan segera konsultasikan bukti kegiatanmu ke Dosen Pengampu agar target wajib 20 SKS tuntas dikonversi ke KRS.',
        ];

        $out = [];
        foreach (array_slice($factors, 0, 3) as $factor) {
            $key = $factor['key'] ?? '';
            if (isset($map[$key])) {
                $out[] = $map[$key];
            }
        }
        if ($level !== 'rendah') {
            $out[] = 'Pertimbangkan mendiskusikan proyeksi ini dengan DPA. Kamu memegang kendali penuh atas izin berbagi data di menu Privasi.';
        }
        if ($out === []) {
            $out[] = 'Pertahankan pola belajarmu dan perbarui nilai UTS, tugas, atau UAS secara berkala untuk memantau target kelulusan.';
        }

        return array_values(array_unique($out));
    }

    private static function classifyCourseFinal(float $score100, float $attendance): string
    {
        if ($attendance < self::MIN_ATTENDANCE_LIMIT || $score100 < Grades::THRESHOLD_C) {
            return 'tinggi';
        }
        if ($score100 < Grades::THRESHOLD_B) {
            return 'sedang';
        }

        return 'rendah';
    }

    private static function levelPoints(string $level): float
    {
        return match ($level) {
            'tinggi' => 1.0,
            'sedang' => 0.5,
            default => 0.0,
        };
    }

    private static function ips(array $courses): float
    {
        $credits = array_sum(array_column($courses, 'credits'));
        if ($credits <= 0) {
            return 0.0;
        }
        $points = 0.0;
        foreach ($courses as $c) {
            $sks = (int) ($c['credits'] ?? 0);
            if (isset($c['grade_point']) && $c['grade_point'] !== null) {
                $gp = (float) $c['grade_point'];
            } else {
                $proj = self::projectCourse($c);
                $gp = Grades::toPoint((string) $proj['projected_score'], 'score100') ?? 0.0;
            }
            $points += $gp * $sks;
        }

        return $points / $credits;
    }

    private static function avgAttendance(array $courses): float
    {
        $credits = array_sum(array_column($courses, 'credits'));
        if ($credits <= 0) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($courses as $c) {
            $sum += ((float) ($c['attendance'] ?? 0.0)) * ((int) ($c['credits'] ?? 0));
        }

        return $sum / $credits;
    }
}