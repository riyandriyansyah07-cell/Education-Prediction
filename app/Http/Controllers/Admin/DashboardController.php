<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LlmAdvisor;
use App\Models\AccountTicket;
use App\Models\Consent;
use App\Models\EnrichmentRecord;
use App\Models\ModelVersion;
use App\Models\RiskResult;
use App\Models\SkpiDocument;
use App\Models\StudentSetting;
use App\Models\User;
use App\Support\EnrichmentCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public const UNASSIGNED_PRODI = 'Belum ditetapkan';

    public const UNASSIGNED_ANGKATAN = 'Tidak diketahui';

    /** Dasbor eksekutif Admin: ringkasan keseluruhan pengguna, grafik prodi/angkatan, matriks kohort (Angkatan x Prodi), DPA, dan analisis risiko semua mahasiswa. */
    public function index(Request $request): View
    {
        $data = $this->buildDataset($request);

        $data['aiHealth'] = LlmAdvisor::healthStatus();

        return view('admin.dashboard', $data);
    }

    /** Ekspor CSV rekap analisis risiko seluruh mahasiswa sesuai filter aktif. */
    public function export(Request $request): StreamedResponse
    {
        $data = $this->buildDataset($request);

        $rows = [[
            'NIM',
            'Nama Mahasiswa',
            'Program Studi',
            'Angkatan',
            'Status Akun',
            'DPA',
            'Izin DPA',
            'Tingkat Risiko',
            'Skor Risiko',
            'Semester',
            'IPS',
            'Kehadiran (%)',
            'SKS Pengayaan Terkonversi',
            'Status Tindak Lanjut',
            'Faktor Utama',
        ]];

        foreach ($data['filteredStudents'] as $row) {
            /** @var User $student */
            $student = $row['student'];
            $res = $row['result'];
            $features = is_array($res?->features) ? $res->features : [];

            $rows[] = [
                $student->username,
                $student->name,
                $row['program_studi'],
                $row['angkatan'],
                $student->is_active ? 'Aktif' : 'Nonaktif',
                $student->dpa?->name ?? 'Belum ditetapkan',
                $row['consent_granted'] ? 'Diizinkan' : 'Belum',
                strtoupper($row['level']),
                $res ? number_format((float) $res->score, 2, '.', '') : '-',
                $res?->semester ?? '-',
                isset($features['ips']) ? number_format((float) $features['ips'], 2, '.', '') : '-',
                isset($features['attendance']) ? number_format((float) $features['attendance'], 1, '.', '') : '-',
                $row['enrichment_converted_sks'].' / '.EnrichmentCatalog::TARGET_SKS,
                StudentSetting::followUpLabelFor($row['follow_up_status']),
                $row['top_factor'] ?? '-',
            ];
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        foreach ($rows as $r) {
            fputcsv($stream, $r);
        }
        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        $filename = 'rekap-analisis-risiko-mahasiswa-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(fn () => print ($csv), $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Kumpulkan seluruh metrik agregasi dan daftar mahasiswa terfilter.
     *
     * @return array<string,mixed>
     */
    private function buildDataset(Request $request): array
    {
        $students = User::where('role', User::ROLE_STUDENT)
            ->with('dpa')
            ->orderBy('username')
            ->orderBy('name')
            ->get();

        $dpas = User::where('role', User::ROLE_DPA)
            ->orderBy('name')
            ->get();

        $adminCount = User::where('role', User::ROLE_ADMIN)->count();
        $activeAdminCount = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->count();

        $studentRefs = $students->pluck('student_ref')->filter()->values()->all();

        // Ambil hasil risiko semester terbaru untuk setiap mahasiswa
        $latestRisks = collect();
        $allRisks = collect();
        if ($studentRefs !== []) {
            $allRisks = RiskResult::whereIn('student_ref', $studentRefs)
                ->orderBy('semester')
                ->get();
            foreach ($allRisks as $r) {
                $latestRisks->put($r->student_ref, $r);
            }
        }

        // Ambil status izin DPA aktif per mahasiswa
        $grantedConsentRefs = $studentRefs !== []
            ? Consent::whereIn('student_ref', $studentRefs)
                ->where('granted', true)
                ->pluck('student_ref')
                ->flip()
                ->all()
            : [];

        // Ambil status tindak lanjut dari student_settings
        $settingsByRef = $studentRefs !== []
            ? StudentSetting::whereIn('student_ref', $studentRefs)
                ->get()
                ->keyBy('student_ref')
            : collect();

        // Fallback program_studi dari account_tickets jika users.program_studi masih kosong
        $ticketProdiByUsername = [];
        if (Schema::hasTable('account_tickets') && Schema::hasColumn('account_tickets', 'program_studi')) {
            $ticketRows = AccountTicket::whereNotNull('program_studi')
                ->where('program_studi', '!=', '')
                ->orderByDesc('id')
                ->get(['username', 'program_studi']);
            foreach ($ticketRows as $tr) {
                if (! isset($ticketProdiByUsername[$tr->username])) {
                    $ticketProdiByUsername[$tr->username] = $tr->program_studi;
                }
            }
        }

        // Hitung SKS Pengayaan terkonversi per mahasiswa bila tabel tersedia
        $enrichmentSksByRef = [];
        if ($studentRefs !== [] && Schema::hasTable('enrichment_records')) {
            $enrRecords = EnrichmentRecord::whereIn('student_ref', $studentRefs)
                ->where('status', EnrichmentRecord::STATUS_DIKONVERSI)
                ->get();
            foreach ($enrRecords as $er) {
                if (strtoupper((string) $er->grade) === 'E') {
                    continue;
                }
                $enrichmentSksByRef[$er->student_ref] = ($enrichmentSksByRef[$er->student_ref] ?? 0) + (int) $er->credits;
            }
        }

        // Ambil bukti SKPI per mahasiswa bila tabel tersedia
        $skpiDocsByRef = [];
        $pendingSkpiProofs = 0;
        if ($studentRefs !== [] && Schema::hasTable('skpi_documents')) {
            $skpiDocs = SkpiDocument::whereIn('student_ref', $studentRefs)->get();
            foreach ($skpiDocs as $doc) {
                $skpiDocsByRef[$doc->student_ref][$doc->type] = $doc->verification_status;
                if ($doc->verification_status === SkpiDocument::STATUS_MENUNGGU) {
                    $pendingSkpiProofs++;
                }
            }
        }

        // Susun baris lengkap per mahasiswa
        $allStudentRows = $students->map(function (User $student) use (
            $latestRisks,
            $grantedConsentRefs,
            $settingsByRef,
            $ticketProdiByUsername,
            $enrichmentSksByRef
        ) {
            $ref = (string) $student->student_ref;
            /** @var RiskResult|null $res */
            $res = $ref !== '' ? $latestRisks->get($ref) : null;
            $features = is_array($res?->features) ? $res->features : [];
            $factors = is_array($res?->factors) ? $res->factors : [];
            $setting = $ref !== '' ? $settingsByRef->get($ref) : null;

            $prodi = trim((string) ($student->program_studi ?: ($ticketProdiByUsername[$student->username] ?? '')));
            if ($prodi === '') {
                $prodi = self::UNASSIGNED_PRODI;
            }

            $entryYear = $student->entryYear();
            $angkatan = $entryYear !== null ? (string) $entryYear : self::UNASSIGNED_ANGKATAN;
            $level = $res ? (string) $res->level : 'belum';

            return [
                'student' => $student,
                'result' => $res,
                'level' => $level,
                'score' => $res ? (float) $res->score : null,
                'semester' => $res?->semester,
                'ips' => isset($features['ips']) ? (float) $features['ips'] : null,
                'attendance' => isset($features['attendance']) ? (float) $features['attendance'] : null,
                'top_factor' => $factors !== [] ? ($factors[0]['label'] ?? null) : null,
                'program_studi' => $prodi,
                'angkatan' => $angkatan,
                'consent_granted' => $student->dpa_id !== null,
                'follow_up_status' => $setting?->follow_up_status ?? StudentSetting::STATUS_BELUM,
                'enrichment_converted_sks' => $ref !== '' ? (int) ($enrichmentSksByRef[$ref] ?? 0) : 0,
            ];
        });

        // Ringkasan risiko keseluruhan
        $riskCounts = [
            'semua' => $allStudentRows->count(),
            'tinggi' => $allStudentRows->where('level', 'tinggi')->count(),
            'sedang' => $allStudentRows->where('level', 'sedang')->count(),
            'rendah' => $allStudentRows->where('level', 'rendah')->count(),
            'belum' => $allStudentRows->where('level', 'belum')->count(),
        ];

        $totalStudents = $allStudentRows->count();
        $riskPcts = [
            'tinggi' => $totalStudents > 0 ? round(($riskCounts['tinggi'] / $totalStudents) * 100, 1) : 0.0,
            'sedang' => $totalStudents > 0 ? round(($riskCounts['sedang'] / $totalStudents) * 100, 1) : 0.0,
            'rendah' => $totalStudents > 0 ? round(($riskCounts['rendah'] / $totalStudents) * 100, 1) : 0.0,
            'belum' => $totalStudents > 0 ? round(($riskCounts['belum'] / $totalStudents) * 100, 1) : 0.0,
        ];

        // Rekap per Program Studi (untuk grafik batang & dropdown filter)
        $prodiStats = $this->aggregateBy($allStudentRows, 'program_studi', sortDescByKey: false);

        // Rekap per Angkatan (untuk grafik batang & dropdown filter)
        $angkatanStats = $this->aggregateBy($allStudentRows, 'angkatan', sortDescByKey: true);

        // Matriks Kohort Gabungan: Angkatan x Program Studi
        $cohortStats = $allStudentRows
            ->groupBy(fn (array $r) => $r['angkatan'].'|'.$r['program_studi'])
            ->map(function (Collection $group) {
                $first = $group->first();
                $ipsValues = $group->pluck('ips')->filter(fn ($v) => $v !== null);
                $attValues = $group->pluck('attendance')->filter(fn ($v) => $v !== null);

                return [
                    'angkatan' => $first['angkatan'],
                    'program_studi' => $first['program_studi'],
                    'total' => $group->count(),
                    'tinggi' => $group->where('level', 'tinggi')->count(),
                    'sedang' => $group->where('level', 'sedang')->count(),
                    'rendah' => $group->where('level', 'rendah')->count(),
                    'belum' => $group->where('level', 'belum')->count(),
                    'avg_ips' => $ipsValues->isNotEmpty() ? round((float) $ipsValues->avg(), 2) : null,
                    'avg_attendance' => $attValues->isNotEmpty() ? round((float) $attValues->avg(), 1) : null,
                ];
            })
            ->sortBy(function (array $item) {
                $isUnknownYear = $item['angkatan'] === self::UNASSIGNED_ANGKATAN;
                $isUnassignedProdi = $item['program_studi'] === self::UNASSIGNED_PRODI;

                return [
                    $isUnknownYear ? 1 : 0,
                    $isUnknownYear ? 0 : -1 * (int) $item['angkatan'],
                    $isUnassignedProdi ? 1 : 0,
                    -1 * $item['total'],
                    $item['program_studi'],
                ];
            })
            ->values();

        // Rekap per DPA
        $dpaStats = $dpas->map(function (User $dpa) use ($allStudentRows) {
            $advisees = $allStudentRows->filter(fn (array $r) => $r['student']->dpa_id === $dpa->id);

            return [
                'dpa' => $dpa,
                'total' => $advisees->count(),
                'consented' => $advisees->where('consent_granted', true)->count(),
                'tinggi' => $advisees->where('level', 'tinggi')->count(),
                'sedang' => $advisees->where('level', 'sedang')->count(),
                'rendah' => $advisees->where('level', 'rendah')->count(),
                'belum' => $advisees->where('level', 'belum')->count(),
            ];
        })->values();

        // Filter daftar detail analisis risiko mahasiswa
        $q = trim((string) $request->query('q', ''));
        $prodiFilter = trim((string) $request->query('prodi', ''));
        $angkatanFilter = trim((string) $request->query('angkatan', ''));
        $levelFilter = trim((string) $request->query('tingkat', ''));
        $dpaFilter = trim((string) $request->query('dpa', ''));

        if (! in_array($levelFilter, ['tinggi', 'sedang', 'rendah', 'belum'], true)) {
            $levelFilter = '';
        }

        $levelOrder = ['tinggi' => 0, 'sedang' => 1, 'rendah' => 2, 'belum' => 3];

        $filteredStudents = $allStudentRows
            ->when($q !== '', function (Collection $col) use ($q) {
                $needle = mb_strtolower($q, 'UTF-8');

                return $col->filter(fn (array $r) => str_contains(mb_strtolower($r['student']->name, 'UTF-8'), $needle)
                    || str_contains(mb_strtolower($r['student']->username, 'UTF-8'), $needle));
            })
            ->when($prodiFilter !== '', fn (Collection $col) => $col->where('program_studi', $prodiFilter))
            ->when($angkatanFilter !== '', fn (Collection $col) => $col->where('angkatan', $angkatanFilter))
            ->when($levelFilter !== '', fn (Collection $col) => $col->where('level', $levelFilter))
            ->when($dpaFilter !== '', function (Collection $col) use ($dpaFilter) {
                if ($dpaFilter === 'belum') {
                    return $col->filter(fn (array $r) => $r['student']->dpa_id === null);
                }

                return $col->filter(fn (array $r) => (string) $r['student']->dpa_id === $dpaFilter);
            })
            ->sortBy(fn (array $r) => [
                $levelOrder[$r['level']] ?? 9,
                -1 * ($r['score'] ?? -1),
                $r['student']->username,
            ])
            ->values();

        $pendingTickets = Schema::hasTable('account_tickets')
            ? AccountTicket::where('status', AccountTicket::STATUS_PENDING)->count()
            : 0;

        $activeModel = ModelVersion::where('is_active', true)->latest('id')->first();

        $prodiOptions = $prodiStats->pluck('label')->all();
        $angkatanOptions = $angkatanStats->pluck('label')->all();

        // Grafik 3: Peta Kuadran Akademik (Kehadiran vs IPS)
        $scatterPoints = [];
        $quadrantCounts = [
            'stabil' => 0,
            'dorong_nilai' => 0,
            'rawan_absen' => 0,
            'prioritas' => 0,
        ];
        foreach ($allStudentRows as $r) {
            if ($r['ips'] === null || $r['attendance'] === null) {
                continue;
            }
            $ipsVal = round(max(0.0, min(4.0, (float) $r['ips'])), 2);
            $attVal = round(max(0.0, min(100.0, (float) $r['attendance'])), 1);

            if ($attVal >= 75.0 && $ipsVal >= 3.0) {
                $quadrantCounts['stabil']++;
            } elseif ($attVal >= 75.0 && $ipsVal < 3.0) {
                $quadrantCounts['dorong_nilai']++;
            } elseif ($attVal < 75.0 && $ipsVal >= 3.0) {
                $quadrantCounts['rawan_absen']++;
            } else {
                $quadrantCounts['prioritas']++;
            }

            $scatterPoints[] = [
                'name' => $r['student']->name,
                'nim' => $r['student']->username,
                'prodi' => $r['program_studi'],
                'angkatan' => $r['angkatan'],
                'ips' => $ipsVal,
                'attendance' => $attVal,
                'score' => $r['score'] !== null ? round((float) $r['score'], 2) : null,
                'level' => $r['level'],
                'semester' => $r['semester'],
            ];
        }

        // Grafik 4A: Kesiapan Kelulusan (20 SKS Pengayaan & 4 Syarat Wajib SKPI)
        $gradBuckets = [
            'pengayaan' => ['label' => 'Pengayaan 20 SKS', 'hint' => 'Minimal 20 SKS terkonversi', 'done' => 0, 'in_progress' => 0, 'pending' => 0],
            'it' => ['label' => 'Sertifikat IT', 'hint' => 'Sertifikat kompetensi IT', 'done' => 0, 'in_progress' => 0, 'pending' => 0],
            'baps' => ['label' => 'BAPS', 'hint' => 'Baitul Arqam Purna Studi', 'done' => 0, 'in_progress' => 0, 'pending' => 0],
            'pkm' => ['label' => 'PKM (Univ+)', 'hint' => 'Minimal lolos tingkat universitas', 'done' => 0, 'in_progress' => 0, 'pending' => 0],
            'toefl' => ['label' => 'TOEFL UHAMKA', 'hint' => 'Sertifikat TOEFL UHAMKA', 'done' => 0, 'in_progress' => 0, 'pending' => 0],
        ];

        foreach ($allStudentRows as $r) {
            $ref = (string) $r['student']->student_ref;
            $setting = $ref !== '' ? $settingsByRef->get($ref) : null;
            $docs = $skpiDocsByRef[$ref] ?? [];

            // 1. Pengayaan 20 SKS
            $sks = (int) $r['enrichment_converted_sks'];
            if ($sks >= EnrichmentCatalog::TARGET_SKS) {
                $gradBuckets['pengayaan']['done']++;
            } elseif ($sks > 0) {
                $gradBuckets['pengayaan']['in_progress']++;
            } else {
                $gradBuckets['pengayaan']['pending']++;
            }

            // 2. Sertifikat IT
            $itDoc = $docs[SkpiDocument::TYPE_IT] ?? null;
            $itStatus = (string) ($setting?->skpi_it_status ?? StudentSetting::SKPI_BELUM);
            if ($itDoc === SkpiDocument::STATUS_TERVERIFIKASI || $itStatus === StudentSetting::SKPI_SELESAI) {
                $gradBuckets['it']['done']++;
            } elseif ($itDoc === SkpiDocument::STATUS_MENUNGGU || $itStatus === StudentSetting::SKPI_PROSES) {
                $gradBuckets['it']['in_progress']++;
            } else {
                $gradBuckets['it']['pending']++;
            }

            // 3. BAPS
            $bapsDoc = $docs[SkpiDocument::TYPE_BAPS] ?? null;
            $bapsStatus = (string) ($setting?->skpi_baps_status ?? StudentSetting::SKPI_BELUM);
            if ($bapsDoc === SkpiDocument::STATUS_TERVERIFIKASI || $bapsStatus === StudentSetting::SKPI_SELESAI) {
                $gradBuckets['baps']['done']++;
            } elseif ($bapsDoc === SkpiDocument::STATUS_MENUNGGU || $bapsStatus === StudentSetting::BAPS_TERDAFTAR) {
                $gradBuckets['baps']['in_progress']++;
            } else {
                $gradBuckets['baps']['pending']++;
            }

            // 4. PKM (Min. Universitas)
            $pkmDoc = $docs[SkpiDocument::TYPE_PKM] ?? null;
            $pkmStage = (string) ($setting?->skpi_pkm_stage ?? StudentSetting::PKM_BELUM);
            if ($pkmDoc === SkpiDocument::STATUS_TERVERIFIKASI || in_array($pkmStage, [StudentSetting::PKM_UNIVERSITAS, StudentSetting::PKM_PENDANAAN], true)) {
                $gradBuckets['pkm']['done']++;
            } elseif ($pkmDoc === SkpiDocument::STATUS_MENUNGGU || $pkmStage === StudentSetting::PKM_FAKULTAS) {
                $gradBuckets['pkm']['in_progress']++;
            } else {
                $gradBuckets['pkm']['pending']++;
            }

            // 5. TOEFL UHAMKA
            $toeflDoc = $docs[SkpiDocument::TYPE_TOEFL] ?? null;
            $toeflStatus = (string) ($setting?->skpi_toefl_status ?? StudentSetting::SKPI_BELUM);
            if ($toeflDoc === SkpiDocument::STATUS_TERVERIFIKASI || $toeflStatus === StudentSetting::SKPI_SELESAI) {
                $gradBuckets['toefl']['done']++;
            } elseif ($toeflDoc === SkpiDocument::STATUS_MENUNGGU || $toeflStatus === StudentSetting::SKPI_PROSES) {
                $gradBuckets['toefl']['in_progress']++;
            } else {
                $gradBuckets['toefl']['pending']++;
            }
        }

        $graduationReadiness = [];
        foreach ($gradBuckets as $k => $b) {
            $donePct = $totalStudents > 0 ? round(($b['done'] / $totalStudents) * 100, 1) : 0.0;
            $progPct = $totalStudents > 0 ? round(($b['in_progress'] / $totalStudents) * 100, 1) : 0.0;
            $graduationReadiness[] = [
                'key' => $k,
                'label' => $b['label'],
                'hint' => $b['hint'],
                'done' => $b['done'],
                'in_progress' => $b['in_progress'],
                'pending' => $b['pending'],
                'total' => $totalStudents,
                'done_pct' => $donePct,
                'progress_pct' => $progPct,
            ];
        }

        // Grafik 4B: Status Tindak Lanjut Bimbingan DPA
        $followUpMeta = [
            StudentSetting::STATUS_BELUM => ['label' => 'Belum dihubungi', 'color' => 'var(--line-strong)'],
            StudentSetting::STATUS_DIJADWALKAN => ['label' => 'Dijadwalkan', 'color' => 'var(--warn)'],
            StudentSetting::STATUS_SUDAH => ['label' => 'Sudah bimbingan', 'color' => 'var(--accent)'],
            StudentSetting::STATUS_SELESAI => ['label' => 'Selesai ditangani', 'color' => 'var(--ok)'],
        ];
        $followUpStats = [];
        foreach ($followUpMeta as $statusKey => $meta) {
            $matched = $allStudentRows->where('follow_up_status', $statusKey);
            $count = $matched->count();
            $priorityCount = $matched->whereIn('level', ['tinggi', 'sedang'])->count();
            $followUpStats[] = [
                'key' => $statusKey,
                'label' => $meta['label'],
                'color' => $meta['color'],
                'count' => $count,
                'priority_count' => $priorityCount,
                'pct' => $totalStudents > 0 ? round(($count / $totalStudents) * 100, 1) : 0.0,
            ];
        }

        // Tren per semester (untuk tab grafik gelombang Per Semester)
        $semesterTrend = $allRisks
            ->groupBy('semester')
            ->sortKeys()
            ->map(function (Collection $group, $sem) {
                $ipsVals = [];
                $attVals = [];
                $scores = [];
                foreach ($group as $r) {
                    $f = is_array($r->features) ? $r->features : [];
                    if (isset($f['ips'])) {
                        $ipsVals[] = max(0.0, min(4.0, (float) $f['ips']));
                    }
                    if (isset($f['attendance'])) {
                        $attVals[] = max(0.0, min(100.0, (float) $f['attendance']));
                    }
                    $scores[] = (float) $r->score;
                }

                return [
                    'label' => 'Sem '.$sem,
                    'semester' => (int) $sem,
                    'count' => $group->count(),
                    'ips' => $ipsVals !== [] ? round(array_sum($ipsVals) / count($ipsVals), 2) : 0.0,
                    'attendance' => $attVals !== [] ? round(array_sum($attVals) / count($attVals), 1) : 0.0,
                    'avg_score' => $scores !== [] ? round(array_sum($scores) / count($scores), 2) : 0.0,
                ];
            })
            ->values()
            ->all();

        // Distribusi gelombang (skalabel untuk puluhan hingga ribuan mahasiswa, 1 sumbu Y = jumlah mahasiswa)
        $analyzedRows = $allStudentRows->filter(fn (array $r) => $r['level'] !== 'belum');
        $analyzedCount = $analyzedRows->count();

        $ipsBuckets = [
            ['label' => '< 2,0', 'count' => 0],
            ['label' => '2,0–2,5', 'count' => 0],
            ['label' => '2,5–3,0', 'count' => 0],
            ['label' => '3,0–3,5', 'count' => 0],
            ['label' => '3,5–3,8', 'count' => 0],
            ['label' => '3,8–4,0', 'count' => 0],
        ];
        $attBuckets = [
            ['label' => '< 60%', 'count' => 0],
            ['label' => '60–74%', 'count' => 0],
            ['label' => '75–84%', 'count' => 0],
            ['label' => '85–94%', 'count' => 0],
            ['label' => '95–100%', 'count' => 0],
        ];
        $riskBuckets = [
            ['label' => '0–2 Aman', 'count' => 0],
            ['label' => '2–4 Rendah', 'count' => 0],
            ['label' => '4–6 Sedang', 'count' => 0],
            ['label' => '6–8 Tinggi', 'count' => 0],
            ['label' => '8–10 Kritis', 'count' => 0],
        ];

        $ipsSum = 0.0; $ipsN = 0; $ipsGood = 0;
        $attSum = 0.0; $attN = 0; $attSafe = 0;
        $riskSum = 0.0; $riskN = 0;

        foreach ($analyzedRows as $r) {
            if ($r['ips'] !== null) {
                $v = (float) $r['ips'];
                $ipsSum += $v; $ipsN++;
                if ($v >= 3.0) { $ipsGood++; }
                if ($v < 2.0) { $ipsBuckets[0]['count']++; }
                elseif ($v < 2.5) { $ipsBuckets[1]['count']++; }
                elseif ($v < 3.0) { $ipsBuckets[2]['count']++; }
                elseif ($v < 3.5) { $ipsBuckets[3]['count']++; }
                elseif ($v < 3.8) { $ipsBuckets[4]['count']++; }
                else { $ipsBuckets[5]['count']++; }
            }
            if ($r['attendance'] !== null) {
                $a = (float) $r['attendance'];
                $attSum += $a; $attN++;
                if ($a >= 75.0) { $attSafe++; }
                if ($a < 60.0) { $attBuckets[0]['count']++; }
                elseif ($a < 75.0) { $attBuckets[1]['count']++; }
                elseif ($a < 85.0) { $attBuckets[2]['count']++; }
                elseif ($a < 95.0) { $attBuckets[3]['count']++; }
                else { $attBuckets[4]['count']++; }
            }
            if ($r['score'] !== null) {
                $s = (float) $r['score'];
                $riskSum += $s; $riskN++;
                if ($s < 2.0) { $riskBuckets[0]['count']++; }
                elseif ($s < 4.0) { $riskBuckets[1]['count']++; }
                elseif ($s < 6.0) { $riskBuckets[2]['count']++; }
                elseif ($s < 8.0) { $riskBuckets[3]['count']++; }
                else { $riskBuckets[4]['count']++; }
            }
        }

        $distributionWaves = [
            'ips' => [
                'buckets' => $ipsBuckets,
                'total' => $ipsN,
                'avg' => $ipsN > 0 ? number_format($ipsSum / $ipsN, 2, ',', '.') : '-',
                'highlight' => $ipsN > 0 ? round(($ipsGood / $ipsN) * 100).'% di atas 3,00' : '-',
                'color' => 'var(--ok)',
            ],
            'attendance' => [
                'buckets' => $attBuckets,
                'total' => $attN,
                'avg' => $attN > 0 ? number_format($attSum / $attN, 1, ',', '.').'%' : '-',
                'highlight' => $attN > 0 ? round(($attSafe / $attN) * 100).'% memenuhi syarat 75%' : '-',
                'color' => 'var(--accent)',
            ],
            'risk' => [
                'buckets' => $riskBuckets,
                'total' => $riskN,
                'avg' => $riskN > 0 ? number_format($riskSum / $riskN, 2, ',', '.').'/10' : '-',
                'highlight' => $analyzedCount.' mahasiswa teranalisis',
                'color' => 'var(--warn)',
            ],
        ];

        return [
            'totals' => [
                'students' => $totalStudents,
                'students_active' => $students->where('is_active', true)->count(),
                'students_analyzed' => $totalStudents - $riskCounts['belum'],
                'students_consented' => $allStudentRows->where('consent_granted', true)->count(),
                'students_without_dpa' => $students->whereNull('dpa_id')->count(),
                'dpas' => $dpas->count(),
                'dpas_active' => $dpas->where('is_active', true)->count(),
                'admins' => $adminCount,
                'admins_active' => $activeAdminCount,
                'pending_tickets' => $pendingTickets,
            ],
            'riskCounts' => $riskCounts,
            'riskPcts' => $riskPcts,
            'prodiStats' => $prodiStats,
            'angkatanStats' => $angkatanStats,
            'cohortStats' => $cohortStats,
            'dpaStats' => $dpaStats,
            'dpas' => $dpas,
            'filteredStudents' => $filteredStudents,
            'activeModel' => $activeModel,
            'prodiOptions' => $prodiOptions,
            'angkatanOptions' => $angkatanOptions,
            'scatterPoints' => $scatterPoints,
            'quadrantCounts' => $quadrantCounts,
            'graduationReadiness' => $graduationReadiness,
            'pendingSkpiProofs' => $pendingSkpiProofs,
            'followUpStats' => $followUpStats,
            'semesterTrend' => $semesterTrend,
            'distributionWaves' => $distributionWaves,
            'filters' => [
                'q' => $q,
                'prodi' => $prodiFilter,
                'angkatan' => $angkatanFilter,
                'tingkat' => $levelFilter,
                'dpa' => $dpaFilter,
            ],
        ];
    }

    /**
     * Agregasi jumlah mahasiswa, distribusi risiko, rata-rata IPS, dan kehadiran berdasarkan kolom kunci.
     *
     * @param  Collection<int,array<string,mixed>>  $rows
     * @return Collection<int,array<string,mixed>>
     */
    private function aggregateBy(Collection $rows, string $key, bool $sortDescByKey): Collection
    {
        $grouped = $rows->groupBy($key)->map(function (Collection $group, string $label) {
            $ipsValues = $group->pluck('ips')->filter(fn ($v) => $v !== null);
            $attValues = $group->pluck('attendance')->filter(fn ($v) => $v !== null);

            return [
                'label' => $label,
                'total' => $group->count(),
                'tinggi' => $group->where('level', 'tinggi')->count(),
                'sedang' => $group->where('level', 'sedang')->count(),
                'rendah' => $group->where('level', 'rendah')->count(),
                'belum' => $group->where('level', 'belum')->count(),
                'avg_ips' => $ipsValues->isNotEmpty() ? round((float) $ipsValues->avg(), 2) : null,
                'avg_attendance' => $attValues->isNotEmpty() ? round((float) $attValues->avg(), 1) : null,
            ];
        });

        if ($sortDescByKey) {
            return $grouped->sortBy(function (array $item) {
                $isUnknown = $item['label'] === self::UNASSIGNED_ANGKATAN;

                return [$isUnknown ? 1 : 0, $isUnknown ? 0 : -1 * (int) $item['label']];
            })->values();
        }

        return $grouped->sortBy(function (array $item) {
            $isUnassigned = $item['label'] === self::UNASSIGNED_PRODI;

            return [$isUnassigned ? 1 : 0, -1 * $item['total'], $item['label']];
        })->values();
    }
}
