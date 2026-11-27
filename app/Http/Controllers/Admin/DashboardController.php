<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountTicket;
use App\Models\EnrichmentRecord;
use App\Models\ModelVersion;
use App\Models\RiskResult;
use App\Models\StudentSetting;
use App\Models\User;
use App\Services\LlmAdvisor;
use App\Support\EnrichmentCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public const UNASSIGNED_PRODI = 'Belum ditetapkan';

    public const UNASSIGNED_ANGKATAN = 'Tidak diketahui';

    /** @var array<string,string> */
    public const EXPORT_COLUMNS = [
        'nim' => 'NIM',
        'nama' => 'Nama Mahasiswa',
        'prodi' => 'Program Studi',
        'angkatan' => 'Angkatan',
        'status_akun' => 'Status Akun',
        'dpa' => 'DPA',
        'izin_dpa' => 'Izin DPA',
        'tingkat' => 'Tingkat Risiko',
        'skor' => 'Skor Risiko',
        'semester' => 'Semester',
        'ips' => 'IPS',
        'kehadiran' => 'Kehadiran (%)',
        'pengayaan' => 'SKS Pengayaan Terkonversi',
        'tindak_lanjut' => 'Status Tindak Lanjut',
        'faktor' => 'Faktor Utama',
    ];

    /**
     * Dasbor eksekutif Admin: ringkasan keseluruhan pengguna, grafik prodi/angkatan,
     * matriks kohort (Angkatan x Prodi), DPA, dan tabel analisis risiko terpaginasi.
     * Mendukung ?format=json untuk live filter, sorting, dan paginasi ribuan data tanpa reload.
     */
    public function index(Request $request): View|JsonResponse
    {
        $data = $this->buildDataset($request);

        if ($request->query('format') === 'json') {
            $paginated = $data['paginatedStudents'];

            return response()->json([
                'total' => $data['totals']['students'],
                'filtered' => $data['pagination']['total'],
                'page' => $data['pagination']['page'],
                'per_page' => $data['pagination']['per_page'],
                'last_page' => $data['pagination']['last_page'],
                'from' => $data['pagination']['from'],
                'to' => $data['pagination']['to'],
                'sort' => $data['sort'],
                'dir' => $data['dir'],
                'rows' => $paginated->map(fn (array $row) => $this->serializeRowForJson($row))->values()->all(),
            ]);
        }

        $data['aiHealth'] = LlmAdvisor::healthStatus();
        $data['exportColumns'] = self::EXPORT_COLUMNS;

        return view('admin.dashboard', $data);
    }

    /**
     * Ekspor laporan analisis risiko sesuai filter, urutan, dan kolom aktif.
     * Mendukung format: csv (default, streaming hemat RAM), xlsx (Excel 2-sheet styled), dan print (siap cetak/PDF).
     */
    public function export(Request $request): StreamedResponse|BinaryFileResponse|View
    {
        $data = $this->buildDataset($request);
        $format = strtolower((string) $request->query('format', 'csv'));
        $selectedCols = $this->resolveSelectedColumns($request);

        if ($format === 'print') {
            return view('admin.dashboard-print', $data + [
                'selectedCols' => $selectedCols,
                'exportColumns' => self::EXPORT_COLUMNS,
                'generatedAt' => now(),
            ]);
        }

        if ($format === 'xlsx') {
            return $this->exportXlsx($data, $selectedCols);
        }

        return $this->exportCsvStream($data, $selectedCols);
    }

    /**
     * Streaming CSV langsung ke php://output per baris agar sanggup mengekspor ribuan data tanpa membebani RAM.
     *
     * @param  array<string,mixed>  $data
     * @param  list<string>  $selectedCols
     */
    private function exportCsvStream(array $data, array $selectedCols): StreamedResponse
    {
        $filename = 'rekap-analisis-risiko-mahasiswa-'.now()->format('Ymd-His').'.csv';
        $headers = array_map(fn (string $key) => self::EXPORT_COLUMNS[$key], $selectedCols);

        return response()->streamDownload(function () use ($data, $selectedCols, $headers) {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headers);

            foreach ($data['filteredStudents'] as $row) {
                $mapped = $this->mapRowForExport($row, neutralizeCsv: true);
                $line = [];
                foreach ($selectedCols as $colKey) {
                    $line[] = $mapped[$colKey] ?? '-';
                }
                fputcsv($stream, $line);
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Ekspor Excel (.xlsx) 2 sheet menggunakan OpenSpout streaming writer:
     * Sheet 1: Ringkasan Eksekutif & Matriks Kohort; Sheet 2: Data Mahasiswa Terfilter.
     *
     * @param  array<string,mixed>  $data
     * @param  list<string>  $selectedCols
     */
    private function exportXlsx(array $data, array $selectedCols): BinaryFileResponse
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'rekap_risiko_').'.xlsx';
        $writer = new XlsxWriter;
        $writer->openToFile($tmpFile);

        $titleStyle = (new Style)->setFontBold()->setFontSize(13)->setFontColor('0F172A');
        $headerStyle = (new Style)->setFontBold()->setFontColor('FFFFFF')->setBackgroundColor('0F766E');
        $subHeaderStyle = (new Style)->setFontBold()->setFontColor('0F172A')->setBackgroundColor('E2E8F0');
        $highStyle = (new Style)->setFontBold()->setFontColor('B91C1C')->setBackgroundColor('FEE2E2');
        $medStyle = (new Style)->setFontBold()->setFontColor('B45309')->setBackgroundColor('FEF3C7');
        $lowStyle = (new Style)->setFontColor('15803D')->setBackgroundColor('DCFCE7');

        // Sheet 1: Ringkasan Eksekutif & Matriks Kohort
        $summarySheet = $writer->getCurrentSheet();
        $summarySheet->setName('Ringkasan Eksekutif');

        $writer->addRow(Row::fromValues(['LAPORAN ANALISIS RISIKO AKADEMIK — FTII UHAMKA'], $titleStyle));
        $writer->addRow(Row::fromValues(['Waktu Ekspor', now()->format('d/m/Y H:i:s'), 'Total Data Terfilter', $data['filteredStudents']->count().' dari '.$data['totals']['students'].' mahasiswa']));
        $activeFilters = array_filter($data['filters']);
        $filterDesc = $activeFilters === []
            ? 'Semua mahasiswa (tanpa filter)'
            : implode(' | ', array_map(fn ($k, $v) => strtoupper((string) $k).': '.$v, array_keys($activeFilters), $activeFilters));
        $writer->addRow(Row::fromValues(['Filter Aktif', $filterDesc]));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(['Ringkasan Proporsi Risiko', 'Jumlah Mahasiswa', 'Persentase (%)'], $headerStyle));
        $writer->addRow(Row::fromValues(['Risiko Tinggi', $data['riskCounts']['tinggi'], $data['riskPcts']['tinggi'].'%'], $highStyle));
        $writer->addRow(Row::fromValues(['Risiko Sedang', $data['riskCounts']['sedang'], $data['riskPcts']['sedang'].'%'], $medStyle));
        $writer->addRow(Row::fromValues(['Risiko Rendah', $data['riskCounts']['rendah'], $data['riskPcts']['rendah'].'%'], $lowStyle));
        $writer->addRow(Row::fromValues(['Belum Dihitung', $data['riskCounts']['belum'], $data['riskPcts']['belum'].'%']));
        $writer->addRow(Row::fromValues(['Total Terdaftar', $data['totals']['students'], '100%'], $subHeaderStyle));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(['Angkatan', 'Program Studi', 'Total', 'Tinggi', 'Sedang', 'Rendah', 'Belum', 'Rata-rata IPS', 'Rata-rata Hadir (%)'], $headerStyle));
        foreach ($data['cohortStats'] as $c) {
            $writer->addRow(Row::fromValues([
                $c['angkatan'],
                $c['program_studi'],
                $c['total'],
                $c['tinggi'],
                $c['sedang'],
                $c['rendah'],
                $c['belum'],
                $c['avg_ips'] !== null ? number_format((float) $c['avg_ips'], 2, '.', '') : '-',
                $c['avg_attendance'] !== null ? number_format((float) $c['avg_attendance'], 1, '.', '') : '-',
            ]));
        }

        // Sheet 2: Data Mahasiswa Terfilter (Streaming)
        $dataSheet = $writer->addNewSheetAndMakeItCurrent();
        $dataSheet->setName('Data Mahasiswa');

        $headers = array_map(fn (string $key) => self::EXPORT_COLUMNS[$key], $selectedCols);
        $writer->addRow(Row::fromValues($headers, $headerStyle));

        foreach ($data['filteredStudents'] as $row) {
            $mapped = $this->mapRowForExport($row, neutralizeCsv: false);
            $line = [];
            foreach ($selectedCols as $colKey) {
                $line[] = $mapped[$colKey] ?? '-';
            }
            $rowStyle = match ($row['level']) {
                'tinggi' => $highStyle,
                'sedang' => $medStyle,
                default => null,
            };
            $writer->addRow(Row::fromValues($line, $rowStyle));
        }

        $writer->close();

        $filename = 'rekap-analisis-risiko-mahasiswa-'.now()->format('Ymd-His').'.xlsx';

        return response()->download($tmpFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Kumpulkan seluruh metrik agregasi, daftar terfilter, urutan, dan paginasi.
     * Dioptimalkan tanpa batas `WHERE IN` dan agregasi SKS langsung di SQL agar siap untuk ribuan mahasiswa.
     *
     * @return array<string,mixed>
     */
    private function buildDataset(Request $request): array
    {
        $students = User::where('role', User::ROLE_STUDENT)
            ->select(['id', 'name', 'username', 'program_studi', 'dpa_id', 'student_ref', 'is_active'])
            ->with(['dpa:id,name,username,is_active'])
            ->orderBy('username')
            ->orderBy('name')
            ->get();

        $dpas = User::where('role', User::ROLE_DPA)
            ->select(['id', 'name', 'username', 'is_active'])
            ->orderBy('name')
            ->get();

        $adminCount = User::where('role', User::ROLE_ADMIN)->count();
        $activeAdminCount = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->count();

        // Ambil hanya hasil risiko semester terbaru per mahasiswa via subquery JOIN (hemat RAM untuk ribuan data)
        $latestSemSub = RiskResult::query()
            ->select('student_ref', DB::raw('MAX(semester) as max_semester'))
            ->groupBy('student_ref');

        $latestRisks = RiskResult::query()
            ->select([
                'risk_results.student_ref',
                'risk_results.semester',
                'risk_results.level',
                'risk_results.score',
                'risk_results.features',
                'risk_results.factors',
            ])
            ->joinSub($latestSemSub, 'lr', function ($join) {
                $join->on('risk_results.student_ref', '=', 'lr.student_ref')
                    ->on('risk_results.semester', '=', 'lr.max_semester');
            })
            ->get()
            ->keyBy('student_ref');

        // Ambil status tindak lanjut secara ringan (hanya kolom yang dibutuhkan)
        $followUpByRef = StudentSetting::query()
            ->pluck('follow_up_status', 'student_ref')
            ->all();

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

        // Agregasi SKS Pengayaan terkonversi langsung di SQL (tanpa memuat seluruh baris model ke memori)
        $enrichmentSksByRef = [];
        if (Schema::hasTable('enrichment_records')) {
            $enrichmentSksByRef = EnrichmentRecord::query()
                ->select('student_ref', DB::raw('SUM(credits) as total_sks'))
                ->where('status', EnrichmentRecord::STATUS_DIKONVERSI)
                ->where(function ($q) {
                    $q->whereNull('grade')->orWhereRaw('UPPER(grade) != ?', ['E']);
                })
                ->groupBy('student_ref')
                ->pluck('total_sks', 'student_ref')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        // Susun baris lengkap per mahasiswa
        $allStudentRows = $students->map(function (User $student) use (
            $latestRisks,
            $followUpByRef,
            $ticketProdiByUsername,
            $enrichmentSksByRef
        ) {
            $ref = (string) $student->student_ref;
            /** @var RiskResult|null $res */
            $res = $ref !== '' ? $latestRisks->get($ref) : null;
            $features = is_array($res?->features) ? $res->features : [];
            $factors = is_array($res?->factors) ? $res->factors : [];

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
                'follow_up_status' => $ref !== '' ? ($followUpByRef[$ref] ?? StudentSetting::STATUS_BELUM) : StudentSetting::STATUS_BELUM,
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

        $prodiStats = $this->aggregateBy($allStudentRows, 'program_studi', sortDescByKey: false);
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

        // Rekap per DPA (dikelompokkan satu kali dalam O(N) alih-alih O(D*N))
        $rowsByDpa = $allStudentRows->groupBy(fn (array $r) => $r['student']->dpa_id ?? 0);
        $dpaStats = $dpas->map(function (User $dpa) use ($rowsByDpa) {
            /** @var Collection<int,array<string,mixed>> $advisees */
            $advisees = $rowsByDpa->get($dpa->id, collect());

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

        // Sorting & Paginasi
        $allowedSorts = ['risiko', 'nama', 'prodi', 'dpa', 'skor', 'semester', 'ips', 'kehadiran', 'pengayaan'];
        $sort = strtolower(trim((string) $request->query('sort', 'risiko')));
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'risiko';
        }
        $dir = strtolower(trim((string) $request->query('dir', 'asc')));
        if (! in_array($dir, ['asc', 'desc'], true)) {
            $dir = 'asc';
        }

        $perPage = (int) $request->query('per_page', 25);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

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
            });

        $filteredStudents = $this->sortStudentRows($filteredStudents, $sort, $dir);

        $filteredTotal = $filteredStudents->count();
        $lastPage = max(1, (int) ceil($filteredTotal / $perPage));
        $page = max(1, min($lastPage, (int) $request->query('page', 1)));
        $offset = ($page - 1) * $perPage;
        $paginatedStudents = $filteredStudents->slice($offset, $perPage)->values();

        $pendingTickets = Schema::hasTable('account_tickets')
            ? AccountTicket::where('status', AccountTicket::STATUS_PENDING)->count()
            : 0;

        $activeModel = ModelVersion::where('is_active', true)->latest('id')->first();

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
            'paginatedStudents' => $paginatedStudents,
            'pagination' => [
                'total' => $filteredTotal,
                'per_page' => $perPage,
                'page' => $page,
                'last_page' => $lastPage,
                'from' => $filteredTotal > 0 ? $offset + 1 : 0,
                'to' => min($filteredTotal, $offset + $perPage),
            ],
            'sort' => $sort,
            'dir' => $dir,
            'activeModel' => $activeModel,
            'prodiOptions' => $prodiStats->pluck('label')->all(),
            'angkatanOptions' => $angkatanStats->pluck('label')->all(),
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
     * @param  Collection<int,array<string,mixed>>  $rows
     * @return Collection<int,array<string,mixed>>
     */
    private function sortStudentRows(Collection $rows, string $sort, string $dir): Collection
    {
        $levelOrder = ['tinggi' => 0, 'sedang' => 1, 'rendah' => 2, 'belum' => 3];
        $desc = $dir === 'desc';

        $sorted = match ($sort) {
            'nama' => $rows->sortBy(fn (array $r) => [mb_strtolower($r['student']->name, 'UTF-8'), $r['student']->username], SORT_REGULAR, $desc),
            'prodi' => $rows->sortBy(fn (array $r) => [$r['program_studi'], $r['angkatan'], $r['student']->username], SORT_REGULAR, $desc),
            'dpa' => $rows->sortBy(fn (array $r) => [$r['student']->dpa?->name ?? 'ZZZ', $r['student']->username], SORT_REGULAR, $desc),
            'skor' => $rows->sortBy(fn (array $r) => [$r['score'] === null ? ($desc ? -1 : 999) : $r['score'], $r['student']->username], SORT_REGULAR, $desc),
            'semester' => $rows->sortBy(fn (array $r) => [$r['semester'] ?? ($desc ? -1 : 999), $r['student']->username], SORT_REGULAR, $desc),
            'ips' => $rows->sortBy(fn (array $r) => [$r['ips'] === null ? ($desc ? -1 : 999) : $r['ips'], $r['student']->username], SORT_REGULAR, $desc),
            'kehadiran' => $rows->sortBy(fn (array $r) => [$r['attendance'] === null ? ($desc ? -1 : 999) : $r['attendance'], $r['student']->username], SORT_REGULAR, $desc),
            'pengayaan' => $rows->sortBy(fn (array $r) => [$r['enrichment_converted_sks'], $r['student']->username], SORT_REGULAR, $desc),
            default => $desc
                ? $rows->sortByDesc(fn (array $r) => [$levelOrder[$r['level']] ?? 9, $r['score'] ?? -1, $r['student']->username])
                : $rows->sortBy(fn (array $r) => [$levelOrder[$r['level']] ?? 9, -1 * ($r['score'] ?? -1), $r['student']->username]),
        };

        return $sorted->values();
    }

    /**
     * @return list<string>
     */
    private function resolveSelectedColumns(Request $request): array
    {
        $raw = trim((string) $request->query('cols', ''));
        if ($raw === '') {
            return array_keys(self::EXPORT_COLUMNS);
        }

        $requested = array_filter(array_map('trim', explode(',', $raw)));
        $valid = array_values(array_intersect($requested, array_keys(self::EXPORT_COLUMNS)));

        return $valid !== [] ? $valid : array_keys(self::EXPORT_COLUMNS);
    }

    /**
     * @param  array<string,mixed>  $row
     * @return array<string,string>
     */
    private function mapRowForExport(array $row, bool $neutralizeCsv): array
    {
        /** @var User $student */
        $student = $row['student'];
        $res = $row['result'];
        $features = is_array($res?->features) ? $res->features : [];

        $safe = static function (string $val) use ($neutralizeCsv): string {
            if (! $neutralizeCsv || $val === '') {
                return $val;
            }
            $trimmed = ltrim($val, "'");

            return preg_match('/^[=+\-@\t\r]/', $trimmed) && ! is_numeric($trimmed)
                ? "'".$trimmed
                : $val;
        };

        return [
            'nim' => $safe((string) $student->username),
            'nama' => $safe((string) $student->name),
            'prodi' => $safe((string) $row['program_studi']),
            'angkatan' => (string) $row['angkatan'],
            'status_akun' => $student->is_active ? 'Aktif' : 'Nonaktif',
            'dpa' => $safe((string) ($student->dpa?->name ?? 'Belum ditetapkan')),
            'izin_dpa' => $row['consent_granted'] ? 'Diizinkan' : 'Belum',
            'tingkat' => strtoupper((string) $row['level']),
            'skor' => $res ? number_format((float) $res->score, 2, '.', '') : '-',
            'semester' => $res?->semester !== null ? (string) $res->semester : '-',
            'ips' => isset($features['ips']) ? number_format((float) $features['ips'], 2, '.', '') : '-',
            'kehadiran' => isset($features['attendance']) ? number_format((float) $features['attendance'], 1, '.', '') : '-',
            'pengayaan' => $row['enrichment_converted_sks'].' / '.EnrichmentCatalog::TARGET_SKS,
            'tindak_lanjut' => StudentSetting::followUpLabelFor($row['follow_up_status']),
            'faktor' => $safe((string) ($row['top_factor'] ?? '-')),
        ];
    }

    /**
     * @param  array<string,mixed>  $row
     * @return array<string,mixed>
     */
    private function serializeRowForJson(array $row): array
    {
        /** @var User $student */
        $student = $row['student'];

        return [
            'id' => $student->id,
            'name' => $student->name,
            'username' => $student->username,
            'is_active' => (bool) $student->is_active,
            'program_studi' => $row['program_studi'],
            'angkatan' => $row['angkatan'],
            'dpa_name' => $student->dpa?->name ?? 'Belum ditetapkan',
            'consent_granted' => (bool) $row['consent_granted'],
            'level' => $row['level'],
            'score' => $row['score'] !== null ? round((float) $row['score'], 2) : null,
            'semester' => $row['semester'],
            'ips' => $row['ips'] !== null ? round((float) $row['ips'], 2) : null,
            'attendance' => $row['attendance'] !== null ? round((float) $row['attendance'], 1) : null,
            'enrichment_sks' => (int) $row['enrichment_converted_sks'],
            'target_sks' => EnrichmentCatalog::TARGET_SKS,
            'top_factor' => $row['top_factor'] ?? 'Belum ada faktor risiko tercatat',
            'follow_up_label' => StudentSetting::followUpLabelFor($row['follow_up_status']),
            'edit_url' => route('admin.akun.ubah', $student),
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
