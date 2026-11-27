<?php

namespace App\Http\Controllers\Validator;

use App\Http\Controllers\Controller;
use App\Models\AiValidation;
use App\Models\RiskResult;
use App\Models\User;
use App\Services\AccessLogger;
use App\Services\ModelService;
use App\Support\RiskModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    /** @var list<string> */
    private const EXPORT_HEADERS = [
        'NIM',
        'Nama Mahasiswa',
        'Program Studi',
        'Semester',
        'Sumber Analisis',
        'Level AI/Sistem',
        'Skor AI/Sistem',
        'Level Rumus Matematis',
        'Skor Rumus Matematis',
        'Selisih Skor',
        'Sepakat AI vs Rumus',
        'IPS',
        'Kehadiran (%)',
        'Rasio Gagal (%)',
        'Status Validasi',
        'Rekomendasi Level Validator',
        'Koreksi Diterapkan',
        'Catatan Validator',
        'Diverifikasi Oleh',
        'Waktu Validasi',
    ];

    /** Panel Audit & Validasi Hasil Perhitungan AI Gemini vs Model Aturan Matematis (terpaginasi untuk ribuan data). */
    public function index(Request $request): View
    {
        $data = $this->buildAuditDataset($request);

        return view('validator.audit', $data);
    }

    /** Simpan hasil verifikasi Validator AI terhadap satu hasil analisis risiko. */
    public function store(Request $request, RiskResult $result): RedirectResponse
    {
        $data = $request->validate([
            'verdict' => ['required', Rule::in(AiValidation::VERDICTS)],
            'expected_level' => [
                Rule::requiredIf($request->input('verdict') === AiValidation::VERDICT_KOREKSI),
                'nullable',
                Rule::in(['rendah', 'sedang', 'tinggi']),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
            'apply_override' => ['nullable', 'boolean'],
        ], [
            'expected_level.required' => 'Pilih tingkat risiko yang seharusnya jika menandai Perlu Koreksi.',
        ]);

        $features = is_array($result->features) ? $result->features : [];
        $thresholds = ModelService::thresholdsOf($result->modelVersion);
        $ruleScore = round(RiskModel::score($features), 2);
        $ruleLevel = RiskModel::classify($ruleScore, $thresholds);

        $verdict = $data['verdict'];
        $expectedLevel = $verdict === AiValidation::VERDICT_KOREKSI
            ? $data['expected_level']
            : (string) $result->level;
        $applyOverride = $verdict === AiValidation::VERDICT_KOREKSI && $request->boolean('apply_override');

        AiValidation::updateOrCreate(
            ['risk_result_id' => $result->id],
            [
                'validator_id' => $request->user()->id,
                'verdict' => $verdict,
                'ai_level' => (string) $result->level,
                'ai_score' => round((float) $result->score, 2),
                'rule_level' => $ruleLevel,
                'rule_score' => $ruleScore,
                'expected_level' => $expectedLevel,
                'override_applied' => $applyOverride,
                'notes' => ($data['notes'] ?? null) ? trim($data['notes']) : null,
                'validated_at' => now(),
            ]
        );

        if ($applyOverride && $expectedLevel !== '') {
            $result->update(['level' => $expectedLevel]);
        }

        AccessLogger::record($request->user(), (string) $result->student_ref, 'validasi_ai');

        $label = $verdict === AiValidation::VERDICT_SESUAI ? 'Sesuai (Valid)' : 'Perlu Koreksi';
        $extra = $applyOverride ? " Level risiko mahasiswa juga diperbarui menjadi {$expectedLevel}." : '';

        return back()->with('status', "Validasi tersimpan sebagai \"{$label}\".{$extra}");
    }

    /**
     * Ekspor laporan audit validasi AI sesuai filter & urutan aktif.
     * Mendukung format: csv (default, streaming hemat RAM), xlsx (Excel 2-sheet styled), dan print (siap cetak/PDF).
     */
    public function export(Request $request): StreamedResponse|BinaryFileResponse|View
    {
        $data = $this->buildAuditDataset($request);
        $format = strtolower((string) $request->query('format', 'csv'));

        if ($format === 'print') {
            return view('validator.audit-print', $data + [
                'generatedAt' => now(),
            ]);
        }

        if ($format === 'xlsx') {
            return $this->exportXlsx($data);
        }

        return $this->exportCsvStream($data);
    }

    /**
     * Streaming CSV langsung ke php://output per baris agar sanggup mengekspor ribuan data tanpa membebani RAM.
     *
     * @param  array<string,mixed>  $data
     */
    private function exportCsvStream(array $data): StreamedResponse
    {
        $filename = 'laporan-validasi-ai-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, self::EXPORT_HEADERS);

            foreach ($data['allFilteredItems'] as $item) {
                fputcsv($stream, $this->mapItemForExport($item, neutralizeCsv: true));
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Ekspor Excel (.xlsx) 2 sheet menggunakan OpenSpout streaming writer:
     * Sheet 1: Ringkasan Akurasi AI; Sheet 2: Detail Audit AI Terfilter.
     *
     * @param  array<string,mixed>  $data
     */
    private function exportXlsx(array $data): BinaryFileResponse
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'audit_ai_').'.xlsx';
        $writer = new XlsxWriter;
        $writer->openToFile($tmpFile);

        $titleStyle = (new Style)->setFontBold()->setFontSize(13)->setFontColor('0F172A');
        $headerStyle = (new Style)->setFontBold()->setFontColor('FFFFFF')->setBackgroundColor('0F766E');
        $subHeaderStyle = (new Style)->setFontBold()->setFontColor('0F172A')->setBackgroundColor('E2E8F0');
        $okStyle = (new Style)->setFontColor('15803D')->setBackgroundColor('DCFCE7');
        $warnStyle = (new Style)->setFontBold()->setFontColor('B45309')->setBackgroundColor('FEF3C7');
        $badStyle = (new Style)->setFontBold()->setFontColor('B91C1C')->setBackgroundColor('FEE2E2');

        $stats = $data['stats'];
        $validatedPct = $stats['total'] > 0 ? round(($stats['validated'] / $stats['total']) * 100, 1) : 0.0;

        // Sheet 1: Ringkasan Akurasi AI
        $summarySheet = $writer->getCurrentSheet();
        $summarySheet->setName('Ringkasan Akurasi AI');

        $writer->addRow(Row::fromValues(['LAPORAN AUDIT & VALIDASI HASIL AI GEMINI — FTII UHAMKA'], $titleStyle));
        $writer->addRow(Row::fromValues(['Waktu Ekspor', now()->format('d/m/Y H:i:s'), 'Data Terfilter', $data['allFilteredItems']->count().' dari '.$stats['total'].' hasil']));
        $writer->addRow(Row::fromValues(['Cakupan Semester', $data['filters']['cakupan'] === 'semua' ? 'Semua semester' : 'Semester terbaru per mahasiswa']));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(['Metrik Evaluasi AI', 'Nilai', 'Keterangan'], $headerStyle));
        $writer->addRow(Row::fromValues(['Total Hasil Dianalisis', $stats['total'], $stats['ai_count'].' Gemini AI / '.($stats['total'] - $stats['ai_count']).' Aturan/ML']));
        $writer->addRow(Row::fromValues(['Akurasi AI (Sesuai)', $stats['accuracy_pct'] !== null ? $stats['accuracy_pct'].'%' : '-', $stats['sesuai'].' sesuai dari '.$stats['validated'].' tervalidasi'], $okStyle));
        $writer->addRow(Row::fromValues(['Prioritas Selisih (AI vs Rumus)', $stats['disagreement'], 'Beda level risiko atau selisih skor >= 1,5'], $stats['disagreement'] > 0 ? $badStyle : null));
        $writer->addRow(Row::fromValues(['Status Sesuai (Valid)', $stats['sesuai'], 'Terverifikasi cocok oleh Validator AI'], $okStyle));
        $writer->addRow(Row::fromValues(['Status Perlu Koreksi', $stats['koreksi'], 'Memerlukan koreksi label / catatan'], $stats['koreksi'] > 0 ? $warnStyle : null));
        $writer->addRow(Row::fromValues(['Belum Divalidasi', $stats['unvalidated'], 'Progres verifikasi: '.$validatedPct.'% selesai'], $subHeaderStyle));

        // Sheet 2: Detail Audit AI (Streaming)
        $detailSheet = $writer->addNewSheetAndMakeItCurrent();
        $detailSheet->setName('Detail Audit AI');
        $writer->addRow(Row::fromValues(self::EXPORT_HEADERS, $headerStyle));

        foreach ($data['allFilteredItems'] as $item) {
            /** @var AiValidation|null $val */
            $val = $item['validation'];
            $rowStyle = null;
            if ($val?->verdict === AiValidation::VERDICT_KOREKSI || $item['is_disagreement']) {
                $rowStyle = $badStyle;
            } elseif ($val?->verdict === AiValidation::VERDICT_SESUAI) {
                $rowStyle = $okStyle;
            }
            $writer->addRow(Row::fromValues($this->mapItemForExport($item, neutralizeCsv: false), $rowStyle));
        }

        $writer->close();

        $filename = 'laporan-validasi-ai-'.now()->format('Ymd-His').'.xlsx';

        return response()->download($tmpFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string,mixed>  $item
     * @return list<string|int>
     */
    private function mapItemForExport(array $item, bool $neutralizeCsv): array
    {
        /** @var RiskResult $res */
        $res = $item['result'];
        /** @var User|null $stu */
        $stu = $item['student'];
        /** @var AiValidation|null $val */
        $val = $item['validation'];

        $safe = static function (string $v) use ($neutralizeCsv): string {
            if (! $neutralizeCsv || $v === '') {
                return $v;
            }
            $trimmed = ltrim($v, "'");

            return preg_match('/^[=+\-@\t\r]/', $trimmed) && ! is_numeric($trimmed)
                ? "'".$trimmed
                : $v;
        };

        return [
            $safe((string) ($stu?->username ?? substr((string) $res->student_ref, 0, 12))),
            $safe((string) ($stu?->name ?? 'Mahasiswa')),
            $safe((string) ($stu?->program_studi ?? '-')),
            (int) $res->semester,
            $item['source_label'],
            strtoupper((string) $res->level),
            number_format((float) $res->score, 2, '.', ''),
            strtoupper($item['rule_level']),
            number_format($item['rule_score'], 2, '.', ''),
            number_format($item['score_delta'], 2, '.', ''),
            $item['is_disagreement'] ? 'TIDAK SEPAKAT' : 'SEPAKAT',
            $item['ips'] !== null ? number_format($item['ips'], 2, '.', '') : '-',
            $item['attendance'] !== null ? number_format($item['attendance'], 1, '.', '') : '-',
            $item['failed_ratio'] !== null ? number_format($item['failed_ratio'] * 100, 1, '.', '') : '-',
            $val ? $val->verdictLabel() : 'Belum divalidasi',
            $val?->expected_level ? strtoupper($val->expected_level) : '-',
            $val?->override_applied ? 'Ya' : 'Tidak',
            $safe((string) ($val?->notes ?? '-')),
            $safe((string) ($val?->validator?->name ?? '-')),
            $val?->validated_at?->format('Y-m-d H:i') ?? '-',
        ];
    }

    /**
     * Bangun dataset perbandingan AI vs Rumus Matematis, statistik akurasi, sorting, dan paginasi.
     * Dioptimalkan tanpa batas `WHERE IN` agar tetap cepat saat data ribuan baris.
     *
     * @return array<string,mixed>
     */
    private function buildAuditDataset(Request $request): array
    {
        $hasValidationsTable = Schema::hasTable('ai_validations');
        $showAllSemesters = $request->query('cakupan') === 'semua';
        $relations = $hasValidationsTable ? ['modelVersion', 'validation.validator:id,name'] : ['modelVersion'];

        if ($showAllSemesters) {
            $scopedResults = RiskResult::with($relations)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();
        } else {
            $latestSemSub = RiskResult::query()
                ->select('student_ref', DB::raw('MAX(semester) as max_semester'))
                ->groupBy('student_ref');

            $scopedResults = RiskResult::with($relations)
                ->select('risk_results.*')
                ->joinSub($latestSemSub, 'lr', function ($join) {
                    $join->on('risk_results.student_ref', '=', 'lr.student_ref')
                        ->on('risk_results.semester', '=', 'lr.max_semester');
                })
                ->orderByDesc('risk_results.updated_at')
                ->orderByDesc('risk_results.id')
                ->get();
        }

        // Ambil peta mahasiswa tanpa batas WHERE IN agar aman untuk ribuan mahasiswa
        $studentsByRef = User::where('role', User::ROLE_STUDENT)
            ->select(['id', 'name', 'username', 'program_studi', 'student_ref'])
            ->get()
            ->keyBy('student_ref');

        $allItems = $scopedResults->map(function (RiskResult $res) use ($studentsByRef, $hasValidationsTable) {
            $features = is_array($res->features) ? $res->features : [];
            $thresholds = ModelService::thresholdsOf($res->modelVersion);
            $ruleScore = round(RiskModel::score($features), 2);
            $ruleLevel = RiskModel::classify($ruleScore, $thresholds);
            $aiScore = round((float) $res->score, 2);
            $aiLevel = (string) $res->level;
            $delta = round(abs($aiScore - $ruleScore), 2);
            $isDisagreement = ($aiLevel !== $ruleLevel) || $delta >= 1.5;

            $isAi = (bool) $res->llm_generated;
            $sourceLabel = $isAi ? 'Gemini AI' : 'Model Aturan / ML';
            $validation = $hasValidationsTable ? $res->validation : null;

            return [
                'result' => $res,
                'student' => $studentsByRef->get($res->student_ref),
                'validation' => $validation,
                'is_ai' => $isAi,
                'source_label' => $sourceLabel,
                'ai_level' => $aiLevel,
                'ai_score' => $aiScore,
                'rule_level' => $ruleLevel,
                'rule_score' => $ruleScore,
                'score_delta' => $delta,
                'is_disagreement' => $isDisagreement,
                'ips' => isset($features['ips']) ? (float) $features['ips'] : null,
                'ipk' => isset($features['ipk']) ? (float) $features['ipk'] : null,
                'attendance' => isset($features['attendance']) ? (float) $features['attendance'] : null,
                'failed_ratio' => isset($features['failed_ratio']) ? (float) $features['failed_ratio'] : null,
                'ips_delta' => isset($features['ips_delta']) ? (float) $features['ips_delta'] : null,
                'factors' => is_array($res->factors) ? $res->factors : [],
                'suggestions' => is_array($res->suggestions) ? $res->suggestions : [],
            ];
        });

        // Hitung statistik akurasi & kesepakatan
        $total = $allItems->count();
        $aiCount = $allItems->where('is_ai', true)->count();
        $disagreementCount = $allItems->where('is_disagreement', true)->count();
        $sesuaiCount = $allItems->filter(fn (array $i) => $i['validation']?->verdict === AiValidation::VERDICT_SESUAI)->count();
        $koreksiCount = $allItems->filter(fn (array $i) => $i['validation']?->verdict === AiValidation::VERDICT_KOREKSI)->count();
        $validatedCount = $sesuaiCount + $koreksiCount;
        $unvalidatedCount = $total - $validatedCount;
        $accuracyPct = $validatedCount > 0 ? round(($sesuaiCount / $validatedCount) * 100, 1) : null;

        // Filter, sorting, tampilan, & paginasi
        $statusFilter = trim((string) $request->query('status', ''));
        $sourceFilter = trim((string) $request->query('sumber', ''));
        $q = trim((string) $request->query('q', ''));
        $viewMode = $request->query('tampilan') === 'tabel' ? 'tabel' : 'kartu';

        $allowedSorts = ['prioritas', 'delta', 'ai_score', 'rule_score', 'nama', 'semester'];
        $sort = strtolower(trim((string) $request->query('sort', 'prioritas')));
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'prioritas';
        }

        $perPage = (int) $request->query('per_page', 25);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $filtered = $allItems
            ->when($statusFilter === 'belum', fn (Collection $c) => $c->filter(fn (array $i) => $i['validation'] === null))
            ->when($statusFilter === 'sesuai', fn (Collection $c) => $c->filter(fn (array $i) => $i['validation']?->verdict === AiValidation::VERDICT_SESUAI))
            ->when($statusFilter === 'perlu_koreksi', fn (Collection $c) => $c->filter(fn (array $i) => $i['validation']?->verdict === AiValidation::VERDICT_KOREKSI))
            ->when($statusFilter === 'selisih', fn (Collection $c) => $c->where('is_disagreement', true))
            ->when($sourceFilter === 'ai', fn (Collection $c) => $c->where('is_ai', true))
            ->when($sourceFilter === 'aturan', fn (Collection $c) => $c->where('is_ai', false))
            ->when($q !== '', function (Collection $c) use ($q) {
                $needle = mb_strtolower($q, 'UTF-8');

                return $c->filter(function (array $i) use ($needle) {
                    $name = mb_strtolower((string) ($i['student']?->name ?? ''), 'UTF-8');
                    $nim = mb_strtolower((string) ($i['student']?->username ?? ''), 'UTF-8');

                    return str_contains($name, $needle) || str_contains($nim, $needle);
                });
            });

        $filtered = match ($sort) {
            'delta' => $filtered->sortByDesc(fn (array $i) => [$i['score_delta'], $i['ai_score']])->values(),
            'ai_score' => $filtered->sortByDesc(fn (array $i) => [$i['ai_score'], $i['score_delta']])->values(),
            'rule_score' => $filtered->sortByDesc(fn (array $i) => [$i['rule_score'], $i['score_delta']])->values(),
            'nama' => $filtered->sortBy(fn (array $i) => [mb_strtolower((string) ($i['student']?->name ?? 'zzz'), 'UTF-8'), $i['result']->semester])->values(),
            'semester' => $filtered->sortByDesc(fn (array $i) => [$i['result']->semester, $i['score_delta']])->values(),
            default => $filtered->sortBy(fn (array $i) => [
                $i['is_disagreement'] ? 0 : 1,
                $i['validation'] === null ? 0 : 1,
                -1 * $i['score_delta'],
            ])->values(),
        };

        $filteredTotal = $filtered->count();
        $lastPage = max(1, (int) ceil($filteredTotal / $perPage));
        $page = max(1, min($lastPage, (int) $request->query('page', 1)));
        $offset = ($page - 1) * $perPage;
        $paginatedItems = $filtered->slice($offset, $perPage)->values();

        return [
            'items' => $paginatedItems,
            'allFilteredItems' => $filtered,
            'pagination' => [
                'total' => $filteredTotal,
                'per_page' => $perPage,
                'page' => $page,
                'last_page' => $lastPage,
                'from' => $filteredTotal > 0 ? $offset + 1 : 0,
                'to' => min($filteredTotal, $offset + $perPage),
            ],
            'stats' => [
                'total' => $total,
                'ai_count' => $aiCount,
                'disagreement' => $disagreementCount,
                'validated' => $validatedCount,
                'unvalidated' => $unvalidatedCount,
                'sesuai' => $sesuaiCount,
                'koreksi' => $koreksiCount,
                'accuracy_pct' => $accuracyPct,
            ],
            'filters' => [
                'status' => $statusFilter,
                'sumber' => $sourceFilter,
                'q' => $q,
                'cakupan' => $showAllSemesters ? 'semua' : 'terbaru',
                'sort' => $sort,
                'per_page' => $perPage,
                'tampilan' => $viewMode,
            ],
        ];
    }
}
