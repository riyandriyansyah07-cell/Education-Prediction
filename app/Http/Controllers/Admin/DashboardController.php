<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LlmAdvisor;
use App\Models\AccountTicket;
use App\Models\Consent;
use App\Models\EnrichmentRecord;
use App\Models\ModelVersion;
use App\Models\RiskResult;
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