<?php

namespace App\Support;

use App\Models\AcademicRecord;

/**
 * Validasi isi berkas impor KRS, nilai (KHS atau komponen UTS/Tugas/UAS), dan absensi.
 * Mendukung kolom opsional rekap tugas beserta kolom sumbernya (mandiri, olu, gdrive).
 */
final class SheetValidator
{
    public const MAX_ROWS = 500;

    public const REQUIRED = ['semester', 'kode_mk', 'nama_mk', 'sks', 'kehadiran'];

    private const ALIASES = [
        'kode' => 'kode_mk',
        'kodemk' => 'kode_mk',
        'kode_mata_kuliah' => 'kode_mk',
        'mata_kuliah' => 'nama_mk',
        'nama_mata_kuliah' => 'nama_mk',
        'namamk' => 'nama_mk',
        'kehadiran_persen' => 'kehadiran',
        'absensi' => 'kehadiran',
        'absensi_persen' => 'kehadiran',
        'hadir' => 'kehadiran',
        'persen_kehadiran' => 'kehadiran',
        'nilai_huruf' => 'nilai',
        'nilai_angka' => 'nilai',
        'nilai_bobot' => 'nilai',
        'bobot' => 'nilai',
        'keaktifan' => 'nilai_keaktifan',
        'aktif' => 'nilai_keaktifan',
        'skor_keaktifan' => 'nilai_keaktifan',
        'uts' => 'nilai_uts',
        'skor_uts' => 'nilai_uts',
        'tugas' => 'nilai_tugas',
        'rata_tugas' => 'nilai_tugas',
        'rekap_tugas' => 'nilai_tugas',
        'uas' => 'nilai_uas',
        'skor_uas' => 'nilai_uas',
        'sumber' => 'sumber_tugas',
        'source' => 'sumber_tugas',
        'sumber_data' => 'sumber_tugas',
    ];

    /**
     * @param  list<list<string|null>>  $rows  baris pertama berisi judul kolom
     * @return array{records:list<array>,errors:list<array{row:int,message:string}>,semesters:list<int>}
     */
    public static function validate(array $rows, string $ownerNim): array
    {
        $errors = [];
        if ($rows === []) {
            return self::fail('Berkas kosong.');
        }

        $rawHeaders = array_shift($rows);
        $gradeScale = self::detectGradeScale($rawHeaders);
        $header = array_map([self::class, 'normalizeHeader'], $rawHeaders);
        $missing = array_values(array_diff(self::REQUIRED, $header));
        if ($missing !== []) {
            return self::fail('Kolom wajib tidak ditemukan: '.implode(', ', $missing).'. Pakai templat yang disediakan.');
        }
        $col = array_flip($header);

        $dataRows = [];
        foreach ($rows as $i => $cells) {
            if (self::isBlank($cells)) {
                continue;
            }
            $dataRows[$i + 2] = $cells;
        }
        if ($dataRows === []) {
            return self::fail('Berkas tidak berisi baris data.');
        }
        if (count($dataRows) > self::MAX_ROWS) {
            return self::fail('Berkas berisi '.count($dataRows).' baris. Batasnya '.self::MAX_ROWS.' baris.');
        }

        $records = [];
        $seen = [];
        $today = now()->toDateString();

        foreach ($dataRows as $line => $cells) {
            $get = fn (string $name) => isset($col[$name]) ? trim((string) ($cells[$col[$name]] ?? '')) : '';
            $rowErrors = [];

            $semester = $get('semester');
            if (! ctype_digit($semester) || (int) $semester < 1 || (int) $semester > 14) {
                $rowErrors[] = 'Semester harus bilangan 1 sampai 14.';
            }

            $code = self::neutralize($get('kode_mk'));
            if (! preg_match('/^[A-Za-z0-9 .\-_]{2,20}$/', $code)) {
                $rowErrors[] = 'Kode mata kuliah harus 2 sampai 20 karakter (huruf, angka, spasi, titik, strip).';
            }

            $name = self::neutralize($get('nama_mk'));
            if ($name === '' || mb_strlen($name) > 120) {
                $rowErrors[] = 'Nama mata kuliah wajib diisi, maksimal 120 karakter.';
            }

            $sks = $get('sks');
            if (! ctype_digit($sks) || (int) $sks < 1 || (int) $sks > 6) {
                $rowErrors[] = 'SKS harus bilangan 1 sampai 6.';
            }

            // Komponen opsional: Keaktifan, Tugas, UTS, UAS, dan Sumber Tugas
            $keaktifanRaw = $get('nilai_keaktifan');
            $tugasRaw = $get('nilai_tugas');
            $utsRaw = $get('nilai_uts');
            $uasRaw = $get('nilai_uas');
            $sumberRaw = strtolower($get('sumber_tugas'));

            $activityScore = self::parseOptionalScore($keaktifanRaw, 'Nilai keaktifan', $rowErrors);
            $assignmentScore = self::parseOptionalScore($tugasRaw, 'Nilai tugas', $rowErrors);
            $utsScore = self::parseOptionalScore($utsRaw, 'Nilai UTS', $rowErrors);
            $uasScore = self::parseOptionalScore($uasRaw, 'Nilai UAS', $rowErrors);

            $assignmentSource = null;
            if ($sumberRaw !== '' && $sumberRaw !== '-') {
                if (! in_array($sumberRaw, ['mandiri', 'olu', 'gdrive'], true)) {
                    $rowErrors[] = 'Sumber tugas harus berisi mandiri, olu, atau gdrive.';
                } else {
                    $assignmentSource = $sumberRaw;
                }
            } elseif ($assignmentScore !== null) {
                $assignmentSource = AcademicRecord::SOURCE_MANDIRI;
            }

            // Nilai KHS resmi boleh dikosongkan pada semester berjalan (sebelum KHS terbit)
            $rawGrade = $get('nilai');
            $hasOfficialGrade = $rawGrade !== '' && $rawGrade !== '-';
            $point = null;
            $gradeInput = null;
            $isOfficialKhs = false;

            if ($hasOfficialGrade) {
                // C1: Heuristik deteksi bobot IP (0–4,0) pada kolom "nilai" (skala 0–100) agar tidak diam-diam terbaca sebagai E
                $numericGrade = Grades::parseNumber($rawGrade);
                if ($gradeScale === 'score100' && $numericGrade !== null && $numericGrade > 0.0 && $numericGrade <= 4.0) {
                    $rowErrors[] = 'Nilai "'.mb_substr($rawGrade, 0, 12).'" berada di rentang 0–4,0 pada kolom "nilai" (skala 0–100). Jika ini bobot IP (0–4), ubah nama kolom menjadi "nilai_bobot" atau "bobot", atau pakai nilai huruf (A–E) / skor 0–100.';
                } else {
                    $point = Grades::toPoint($rawGrade, $gradeScale);
                    if ($point === null) {
                        $rowErrors[] = 'Nilai "'.mb_substr($rawGrade, 0, 12).'" tidak dikenal. Pakai skala UHAMKA (A sampai E) atau angka 0 sampai 100.';
                    } else {
                        $gradeInput = mb_substr($rawGrade, 0, 12);
                        $isOfficialKhs = true;
                    }
                }
            } elseif (! isset($col['nilai']) && $utsScore === null && $assignmentScore === null && $uasScore === null) {
                $rowErrors[] = 'Kolom nilai tidak ditemukan.';
            }

            $attendance = Grades::parseNumber($get('kehadiran'));
            if ($attendance === null || $attendance < 0.0 || $attendance > 100.0) {
                $rowErrors[] = 'Kehadiran harus angka 0 sampai 100 (persen).';
            }

            if (isset($col['nim']) && $get('nim') !== '' && $get('nim') !== $ownerNim) {
                $rowErrors[] = 'NIM pada berkas tidak sama dengan NIM akunmu.';
            }

            $key = $semester.'|'.strtoupper($code);
            if ($rowErrors === [] && isset($seen[$key])) {
                $rowErrors[] = 'Mata kuliah ini sudah muncul di baris '.$seen[$key].' untuk semester yang sama.';
            }

            if ($rowErrors !== []) {
                foreach ($rowErrors as $message) {
                    $errors[] = ['row' => $line, 'message' => $message];
                }

                continue;
            }

            $proj = RiskModel::projectCourse([
                'credits' => (int) $sks,
                'attendance' => (float) $attendance,
                'activity_score' => $activityScore,
                'grade_point' => $point,
                'grade_input' => $gradeInput,
                'uts_score' => $utsScore,
                'assignment_score' => $assignmentScore,
                'uas_score' => $uasScore,
                'assignment_source' => $assignmentSource,
                'is_official_khs' => $isOfficialKhs,
            ]);

            $seen[$key] = $line;
            $records[] = [
                'semester' => (int) $semester,
                'course_code' => strtoupper($code),
                'course_name' => $name,
                'credits' => (int) $sks,
                'grade_point' => $point,
                'grade_input' => $gradeInput,
                'attendance' => $attendance,
                'activity_score' => $activityScore,
                'uts_score' => $utsScore,
                'assignment_score' => $assignmentScore,
                'uas_score' => $uasScore,
                'assignment_source' => $assignmentSource,
                'assignment_recorded_at' => $assignmentScore !== null ? $today : null,
                'projected_score' => $proj['projected_score'],
                'min_uas_for_c' => $proj['min_uas_for_c'],
                'min_uas_for_b' => $proj['min_uas_for_b'],
                'course_risk_level' => $proj['course_risk_level'],
                'data_trust_level' => $proj['data_trust_level'],
                'is_official_khs' => $isOfficialKhs,
            ];
        }

        if ($errors !== []) {
            return ['records' => [], 'errors' => $errors, 'semesters' => []];
        }

        $semesters = array_values(array_unique(array_column($records, 'semester')));
        sort($semesters);

        return ['records' => $records, 'errors' => [], 'semesters' => $semesters];
    }

    public static function normalizeHeader(?string $h): string
    {
        $h = strtolower(trim((string) $h));
        $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
        $h = preg_replace('/[^a-z0-9]+/', '_', $h);
        $h = trim((string) $h, '_');

        return self::ALIASES[$h] ?? $h;
    }

    /**
     * Strip apostrof awal yang lolos (C2), lalu netralkan karakter rumus dengan satu tanda petik.
     */
    public static function neutralize(string $text): string
    {
        $text = ltrim(trim($text), "'");

        return preg_match('/^[=+\-@\t\r]/', $text) ? "'".$text : $text;
    }

    private static function parseOptionalScore(string $raw, string $label, array &$rowErrors): ?float
    {
        if ($raw === '' || $raw === '-') {
            return null;
        }
        $n = Grades::parseNumber($raw);
        if ($n === null || $n < 0.0 || $n > 100.0) {
            $rowErrors[] = "{$label} harus berupa angka 0 sampai 100 (atau dikosongkan).";

            return null;
        }

        return round($n, 2);
    }

    private static function detectGradeScale(array $rawHeaders): string
    {
        foreach ($rawHeaders as $h) {
            $clean = strtolower(trim((string) $h));
            $clean = preg_replace('/^\xEF\xBB\xBF/', '', $clean);
            $clean = trim((string) preg_replace('/[^a-z0-9]+/', '_', $clean), '_');
            if (in_array($clean, ['nilai_bobot', 'bobot'], true)) {
                return 'bobot';
            }
            if ($clean === 'nilai_huruf') {
                return 'huruf';
            }
        }

        return 'score100';
    }

    private static function isBlank(array $cells): bool
    {
        foreach ($cells as $c) {
            if (trim((string) $c) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function fail(string $message): array
    {
        return ['records' => [], 'errors' => [['row' => 1, 'message' => $message]], 'semesters' => []];
    }
}
