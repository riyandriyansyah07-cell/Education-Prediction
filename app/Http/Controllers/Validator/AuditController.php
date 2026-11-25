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
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    /** Panel Audit & Validasi Hasil Perhitungan AI Gemini vs Model Aturan Matematis. */
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

    /** Ekspor laporan audit validasi AI ke CSV (siap dilampirkan ke laporan/skripsi). */
    public function export(Request $request): StreamedResponse
    {
        $data = $this->buildAuditDataset($request);

        $rows = [[
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
        ]];

        foreach ($data['items'] as $item) {
            /** @var RiskResult $res */
            $res = $item['result'];
            /** @var User|null $stu */
            $stu = $item['student'];
            /** @var AiValidation|null $val */
            $val = $item['validation'];

            $rows[] = [
                $stu?->username ?? substr((string) $res->student_ref, 0, 12),
                $stu?->name ?? 'Mahasiswa',
                $stu?->program_studi ?? '-',
                $res->semester,
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
                $val?->notes ?? '-',
                $val?->validator?->name ?? '-',
                $val?->validated_at?->format('Y-m-d H:i') ?? '-',
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

        return response()->streamDownload(fn () => print ($csv), 'laporan-validasi-ai-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Bangun dataset perbandingan AI vs Rumus Matematis dan statistik akurasi validator.
     *
     * @return array<string,mixed>
     */
    private function buildAuditDataset(Request $request): array
    {
        $hasValidationsTable = Schema::hasTable('ai_validations');

        $query = RiskResult::with($hasValidationsTable ? ['modelVersion', 'validation.validator'] : ['modelVersion'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        $allResults = $query->get();

        // Ambil hasil terbaru per mahasiswa agar tidak duplikat antar semester (atau tampilkan semua jika diminta)
        $showAllSemesters = $request->query('cakupan') === 'semua';
        $scopedResults = $showAllSemesters
            ? $allResults
            : $allResults->groupBy('student_ref')->map(fn (Collection $g) => $g->sortByDesc('semester')->first())->values();

        $refs = $scopedResults->pluck('student_ref')->unique()->values()->all();
        $studentsByRef = $refs !== []
            ? User::whereIn('student_ref', $refs)->get()->keyBy('student_ref')
            : collect();

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

        // Filter
        $statusFilter = trim((string) $request->query('status', ''));
        $sourceFilter = trim((string) $request->query('sumber', ''));
        $q = trim((string) $request->query('q', ''));

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
            })
            ->sortBy(fn (array $i) => [
                $i['is_disagreement'] ? 0 : 1,
                $i['validation'] === null ? 0 : 1,
                -1 * $i['score_delta'],
            ])
            ->values();

        return [
            'items' => $filtered,
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
            ],
        ];
    }
}
