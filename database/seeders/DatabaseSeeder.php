<?php

namespace Database\Seeders;

use App\Models\AcademicCalendar;
use App\Models\AcademicRecord;
use App\Models\Consent;
use App\Models\GuidanceNote;
use App\Models\HistoricalDataset;
use App\Models\HistoricalRecord;
use App\Models\ImportBatch;
use App\Models\ModelVersion;
use App\Models\StudentSetting;
use App\Models\User;
use App\Services\ModelService;
use App\Services\Pseudonym;
use App\Services\RiskService;
use App\Support\Grades;
use App\Support\RiskModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data contoh sintetis (dilabeli dengan jelas bahwa data bersifat sintetis untuk demonstrasi).
 * Semua nama, NIM, dan nilai dibuat mengikuti 10 variabel inti dan skala resmi UHAMKA.
 * Kata sandi semua akun contoh: "password123".
 */
class DatabaseSeeder extends Seeder
{
    private const PASSWORD = 'password123';

    public function run(): void
    {
        $this->ensureLocalPseudonymSalt();

        mt_srand(2026);

        $admin = User::create([
            'name' => 'Sekretariat FTII',
            'username' => 'admin',
            'email' => 'sekretariat@uhamka.test',
            'role' => 'admin',
            'password' => self::PASSWORD,
        ]);

        $dpaA = User::create([
            'name' => 'Dr. Hanafi Rahmat, M.Kom.',
            'username' => '0312058801',
            'role' => 'dpa',
            'password' => self::PASSWORD,
        ]);
        $dpaB = User::create([
            'name' => 'Ir. Sulastri Wibisono, M.T.',
            'username' => '0318077402',
            'role' => 'dpa',
            'password' => self::PASSWORD,
        ]);

        AcademicCalendar::create([
            'academic_year' => '2026/2027',
            'term' => AcademicCalendar::TERM_GASAL,
            'uts_start_date' => now()->subDays(14)->toDateString(),
            'uts_end_date' => now()->subDays(7)->toDateString(),
            'uas_start_date' => now()->addDays(35)->toDateString(),
            'uas_end_date' => now()->addDays(42)->toDateString(),
            'khs_release_date' => now()->addDays(56)->toDateString(),
            'is_current' => true,
            'updated_by' => $admin->id,
        ]);

        $this->seedModel($admin->id);

        // Profil mahasiswa: [nama, NIM, DPA, kehadiran, IPS, peluang gagal, tren, izin, status_tindak_lanjut, keaktifan]
        $students = [
            ['Riyan Adriyansyah', '2303045009', $dpaA, 90, 3.45, 0.00, 0.10, true, StudentSetting::STATUS_BELUM, StudentSetting::ACTIVITY_AKTIF],
            ['Nadia Putri Maharani', '2303045012', $dpaA, 94, 3.62, 0.00, 0.05, true, StudentSetting::STATUS_SELESAI, StudentSetting::ACTIVITY_AKTIF],
            ['Bagas Setiawan', '2303045018', $dpaA, 71, 2.20, 0.30, -0.35, true, StudentSetting::STATUS_DIJADWALKAN, StudentSetting::ACTIVITY_KURANG],
            ['Salsabila Nurfadilah', '2303045021', $dpaA, 83, 2.70, 0.08, -0.10, true, StudentSetting::STATUS_SUDAH, StudentSetting::ACTIVITY_CUKUP],
            ['Farhan Dwi Prakoso', '2303045025', $dpaA, 64, 1.85, 0.45, -0.55, true, StudentSetting::STATUS_BELUM, StudentSetting::ACTIVITY_KURANG],
            ['Citra Anjani Lestari', '2303045027', $dpaA, 88, 3.10, 0.00, 0.05, false, StudentSetting::STATUS_BELUM, null],
            ['Muhammad Rafa Kuswanto', '2303045029', $dpaA, 80, 2.55, 0.12, -0.20, false, StudentSetting::STATUS_BELUM, null],
            ['Yobi Fazri Rinandi', '2303045007', $dpaB, 91, 3.30, 0.00, 0.15, true, StudentSetting::STATUS_SUDAH, StudentSetting::ACTIVITY_AKTIF],
            ['Aulia Rahmawati', '2303045033', $dpaB, 76, 2.35, 0.20, -0.25, true, StudentSetting::STATUS_DIJADWALKAN, StudentSetting::ACTIVITY_CUKUP],
            ['Dimas Aryo Pamungkas', '2303045036', $dpaB, 86, 2.95, 0.04, 0.00, true, StudentSetting::STATUS_BELUM, StudentSetting::ACTIVITY_AKTIF],
            ['Keisha Zahra Amelia', '2303045040', $dpaB, 69, 2.05, 0.35, -0.45, false, StudentSetting::STATUS_BELUM, null],
            ['Reza Fadillah', '2303045044', $dpaB, 92, 3.50, 0.00, 0.20, true, StudentSetting::STATUS_SELESAI, StudentSetting::ACTIVITY_AKTIF],
        ];

        foreach ($students as $i => [$name, $nim, $dpa, $attendance, $ips, $failRate, $trend, $consent, $followUp, $activity]) {
            $user = User::create([
                'name' => $name,
                'username' => $nim,
                'email' => null,
                'role' => 'mahasiswa',
                'password' => self::PASSWORD,
                'student_ref' => Pseudonym::ref($nim),
                'dpa_id' => $dpa->id,
            ]);

            StudentSetting::create([
                'student_ref' => $user->student_ref,
                'remind_in_app' => true,
                'remind_email' => false,
                'follow_up_status' => $followUp,
                'dpa_activity_level' => $consent ? $activity : null,
                'dpa_updated_by' => $consent && $activity ? $dpa->id : null,
            ]);

            if ($i !== 0 && $consent) {
                Consent::create([
                    'student_ref' => $user->student_ref,
                    'dpa_id' => $dpa->id,
                    'granted' => true,
                    'changed_at' => now()->subDays(mt_rand(1, 20)),
                ]);
            }

            // Akun demo mahasiswa pertama (Riyan) dimulai kosong agar alur impor bisa langsung dicoba
            if ($i !== 0) {
                $this->seedAcademic($user->student_ref, $attendance, $ips, $failRate, $trend);

                if ($consent && in_array($followUp, [StudentSetting::STATUS_SUDAH, StudentSetting::STATUS_SELESAI], true)) {
                    GuidanceNote::create([
                        'student_ref' => $user->student_ref,
                        'dpa_id' => $dpa->id,
                        'semester' => 6,
                        'guidance_date' => now()->subDays(mt_rand(2, 10))->toDateString(),
                        'topic' => 'Evaluasi Proyeksi Nilai UTS & Kehadiran Semester Berjalan',
                        'follow_up_action' => 'Menyusun jadwal belajar mingguan untuk mengejar nilai UAS minimal (U_min) di atas batas C (56).',
                        'follow_up_status' => $followUp,
                        'activity_assessment' => $activity,
                        'confirmed_course_scores' => true,
                    ]);
                }
            }
        }
    }

    /**
     * Pastikan PSEUDONYM_SALT terisi saat instalasi lokal agar `migrate --seed` tidak gagal.
     * Di produksi, PSEUDONYM_SALT tetap wajib diisi secara eksplisit di .env.
     */
    private function ensureLocalPseudonymSalt(): void
    {
        if (trim((string) config('risk.pseudonym_salt')) !== '') {
            return;
        }

        if (! app()->environment('local', 'testing')) {
            return;
        }

        $salt = Str::random(32);
        config(['risk.pseudonym_salt' => $salt]);

        $envPath = base_path('.env');
        if (app()->environment('local') && is_file($envPath) && is_writable($envPath)) {
            $envContent = (string) file_get_contents($envPath);
            if (preg_match('/^PSEUDONYM_SALT=.*$/m', $envContent)) {
                $updated = preg_replace('/^PSEUDONYM_SALT=\s*$/m', 'PSEUDONYM_SALT='.$salt, $envContent);
                if (is_string($updated)) {
                    file_put_contents($envPath, $updated);
                }
            } else {
                file_put_contents($envPath, rtrim($envContent).PHP_EOL.'PSEUDONYM_SALT='.$salt.PHP_EOL);
            }
        }

        $this->command?->info('PSEUDONYM_SALT kosong pada environment local — garam acak otomatis dibuat dan disimpan ke .env.');
    }

    /** Dataset historis sintetis 10 variabel inti, ditandai jelas dengan is_synthetic = true. */
    private function seedModel(?int $uploadedBy = null): void
    {
        $profiles = [
            'tinggi' => [68, 8, 1.9, 0.35, 0.18, 0.50, -0.45, 0.30],
            'sedang' => [79, 6, 2.45, 0.30, 0.04, 0.20, -0.12, 0.25],
            'rendah' => [91, 5, 3.30, 0.28, 0.00, 0.05, 0.10, 0.20],
        ];

        $rows = [];
        for ($i = 0; $i < 400; $i++) {
            $r = mt_rand(1, 100);
            $label = $r <= 22 ? 'tinggi' : ($r <= 57 ? 'sedang' : 'rendah');
            [$am, $as, $im, $is, $fl, $fh, $dm, $ds] = $profiles[$label];

            $att = round(max(35, min(100, $this->gauss($am, $as))), 2);
            $minAtt = round(max(20, min($att, $att - abs($this->gauss(6, 4)))), 2);
            $lowAttCount = $minAtt < 75.0 ? ($att < 72.0 ? 2 : 1) : 0;
            $attDelta = round(max(-35, min(25, $this->gauss($dm * 12, 5))), 2);
            $ips = round(max(0.5, min(4.0, $this->gauss($im, $is))), 2);
            $ipsDelta = round(max(-3.0, min(3.0, $this->gauss($dm, $ds))), 2);
            $ipk = round(max(0.8, min(4.0, $ips - ($ipsDelta * 0.35) + $this->gauss(0, 0.12))), 2);
            $failedRatio = round($fl + ($fh - $fl) * mt_rand() / mt_getrandmax(), 4);
            $repeatCourses = $failedRatio >= 0.25 ? 2 : ($failedRatio >= 0.10 ? 1 : 0);
            $creditsDelta = $label === 'tinggi' ? mt_rand(2, 6) : ($label === 'sedang' ? mt_rand(-2, 4) : mt_rand(-2, 2));

            $rows[] = [
                'attendance' => $att,
                'min_attendance' => $minAtt,
                'low_attendance_courses' => $lowAttCount,
                'attendance_delta' => $attDelta,
                'ips' => $ips,
                'ipk' => $ipk,
                'ips_delta' => $ipsDelta,
                'failed_ratio' => $failedRatio,
                'repeat_courses' => $repeatCourses,
                'credits_delta' => $creditsDelta,
                'label' => $label,
            ];
        }
        shuffle($rows);

        $counts = array_count_values(array_column($rows, 'label')) + ['rendah' => 0, 'sedang' => 0, 'tinggi' => 0];
        $dataset = HistoricalDataset::create([
            'name' => 'data-historis-sintetis-10-variabel.csv',
            'row_count' => count($rows),
            'label_counts' => $counts,
            'is_synthetic' => true,
            'uploaded_by' => $uploadedBy,
        ]);

        foreach (array_chunk($rows, 200) as $chunk) {
            HistoricalRecord::insert(array_map(fn ($r) => $r + ['historical_dataset_id' => $dataset->id], $chunk));
        }

        $models = app(ModelService::class);
        $trained = $models->train($dataset);
        if ($trained->meets_target) {
            $models->activate($trained);
        } else {
            ModelVersion::create([
                'version' => 'aturan-awal',
                'algorithm' => 'Aturan berbobot (10 variabel inti), ambang bawaan',
                'params' => ['sedang' => 2.5, 'tinggi' => 5.0, 'engine' => 'rule'],
                'meets_target' => true,
                'is_active' => true,
                'trained_at' => now(),
            ]);
        }
    }

    private function seedAcademic(string $ref, float $attendance, float $ips, float $failRate, float $trend): void
    {
        $catalog = [
            ['Algoritma dan Pemrograman', 'IF101', 3], ['Matematika Diskrit', 'IF102', 3], ['Pengantar Sistem Informasi', 'IF103', 2], ['Bahasa Inggris Teknik', 'UM101', 2],
            ['Struktur Data', 'IF201', 3], ['Basis Data', 'IF202', 3], ['Sistem Operasi', 'IF203', 3], ['Statistika', 'UM201', 2],
            ['Pemrograman Web', 'IF301', 3], ['Rekayasa Perangkat Lunak', 'IF302', 3], ['Jaringan Komputer', 'IF303', 3], ['Analisis dan Perancangan Sistem', 'IF304', 3],
            ['Kecerdasan Buatan', 'IF401', 3], ['Interaksi Manusia dan Komputer', 'IF402', 2], ['Manajemen Proyek TI', 'IF403', 3], ['Sistem Pendukung Keputusan', 'IF404', 3],
            ['Big Data', 'IF501', 3], ['Keamanan Informasi', 'IF502', 3], ['Pemrograman Lanjut', 'IF503', 3], ['Tata Kelola TI', 'IF504', 2],
            ['Pemrograman Mobile', 'IF601', 3], ['Data Mining', 'IF602', 3], ['Seminar Capstone Project', 'IF603', 3], ['Etika Profesi', 'UM601', 2],
        ];

        $now = now();
        $batch = ImportBatch::create([
            'student_ref' => $ref,
            'source_name' => 'portal-akademik-sintetis.csv',
            'rows_valid' => 24,
            'semesters' => [1, 2, 3, 4, 5, 6],
        ]);
        $records = [];

        foreach ($catalog as $i => [$name, $code, $credits]) {
            $semester = intdiv($i, 4) + 1;
            $drift = $trend * max(0, $semester - 3) / 3;
            $semIps = max(0.4, min(4.0, $ips + $drift + $this->gauss(0, 0.2)));
            $failed = mt_rand() / mt_getrandmax() < $failRate && $semester >= 5;
            $point = $failed ? 1.0 : (float) max(2, min(4, (int) round($this->gauss($semIps, 0.35))));
            $att = round(max(35, min(100, $attendance + $drift * 6 + $this->gauss(0, 4))), 2);

            // Semester 6 disimulasikan sebagai semester berjalan (setelah UTS & Tugas) untuk mendemonstrasikan proyeksi U_min
            $isCurrentSem = ($semester === 6);
            $utsScore = $isCurrentSem ? round(max(35, min(95, ($point * 20) + $this->gauss(5, 6))), 2) : null;
            $tugasScore = $isCurrentSem ? round(max(45, min(98, ($point * 21) + $this->gauss(6, 5))), 2) : null;
            $source = $isCurrentSem ? AcademicRecord::SOURCE_OLU : null;

            $proj = RiskModel::projectCourse([
                'credits' => $credits,
                'attendance' => $att,
                'grade_point' => $isCurrentSem ? null : $point,
                'grade_input' => $isCurrentSem ? null : Grades::pointToLetter($point),
                'uts_score' => $utsScore,
                'assignment_score' => $tugasScore,
                'uas_score' => null,
                'assignment_source' => $source,
                'is_official_khs' => ! $isCurrentSem,
            ]);

            $records[] = [
                'student_ref' => $ref,
                'import_batch_id' => $batch->id,
                'semester' => $semester,
                'course_code' => $code,
                'course_name' => $name,
                'credits' => $credits,
                'grade_point' => $isCurrentSem ? null : $point,
                'grade_input' => $isCurrentSem ? null : Grades::pointToLetter($point),
                'attendance' => $att,
                'uts_score' => $utsScore,
                'assignment_score' => $tugasScore,
                'uas_score' => null,
                'assignment_source' => $source,
                'assignment_recorded_at' => $isCurrentSem ? $now->toDateString() : null,
                'projected_score' => $proj['projected_score'],
                'min_uas_for_c' => $proj['min_uas_for_c'],
                'min_uas_for_b' => $proj['min_uas_for_b'],
                'course_risk_level' => $proj['course_risk_level'],
                'data_trust_level' => $proj['data_trust_level'],
                'is_official_khs' => ! $isCurrentSem,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        AcademicRecord::insert($records);
        app(RiskService::class)->refresh($ref);
    }

    private function gauss(float $mean, float $sd): float
    {
        $u = 1 - mt_rand() / mt_getrandmax();
        $v = mt_rand() / mt_getrandmax();

        return $mean + $sd * sqrt(-2 * log($u)) * cos(2 * M_PI * $v);
    }
}
