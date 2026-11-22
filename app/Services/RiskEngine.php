<?php

namespace App\Services;

use App\Models\ModelVersion;
use App\Support\RiskModel;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Proses 3.0: menghitung tingkat risiko, skor, faktor penyebab, dan saran.
 *
 * Mendukung tiga mode melalui RISK_ENGINE di .env:
 * - "gemini" (AI Utama Rekomendasi): Gemini/LLM menilai 10 variabel inti & proyeksi semester terbaru
 *   untuk menentukan skor, level, faktor, kalimat penyemangat, dan saran via Structured JSON Output;
 *   otomatis jatuh ke model aturan PHP (RiskModel) bila API Key kosong atau timeout.
 * - "http": Memanggil layanan model Python (Random Forest / XGBoost + SHAP).
 * - "rule": Menggunakan model aturan berbobot di PHP.
 */
class RiskEngine
{
    public const FEATURES = RiskModel::CORE_FEATURES;

    public function __construct(
        private ModelService $models,
        private ?LlmAdvisor $llm = null
    ) {
        $this->llm ??= app(LlmAdvisor::class);
    }

    /**
     * @param  list<array<string,mixed>>  $featureSets
     * @param  list<list<array<string,mixed>>>  $courseProjectionsList
     * @return list<array{
     *   score:float,
     *   level:string,
     *   factors:array,
     *   suggestions?:list<string>,
     *   encouragement?:string|null,
     *   llm_generated?:bool,
     *   model_version_id:int|null,
     *   explanation_unavailable?:bool
     * }>
     */
    public function assessMany(array $featureSets, array $courseProjectionsList = []): array
    {
        if ($featureSets === []) {
            return [];
        }

        $engine = strtolower(trim((string) config('risk.engine', 'gemini')));

        // 1. Mode AI Utama: Gemini / LLM menilai semester terbaru sebagai penentu parameter risiko utama
        if (in_array($engine, ['gemini', 'llm'], true)) {
            $results = $this->rule($featureSets);
            $lastIdx = count($featureSets) - 1;
            $latestFeatures = $featureSets[$lastIdx];
            $latestCourses = $courseProjectionsList[$lastIdx] ?? [];

            $geminiResult = $this->llm->assessRisk($latestFeatures, $latestCourses);
            if ($geminiResult !== null) {
                $results[$lastIdx] = [
                    'score' => $geminiResult['score'],
                    'level' => $geminiResult['level'],
                    'factors' => $geminiResult['factors'],
                    'suggestions' => $geminiResult['suggestions'],
                    'encouragement' => $geminiResult['encouragement'] ?? null,
                    'llm_generated' => true,
                    'model_version_id' => $results[$lastIdx]['model_version_id'],
                    'explanation_unavailable' => false,
                ];
            }

            return $results;
        }

        // 2. Mode layanan ML Python (FastAPI)
        $active = $this->models->active();
        if ($active && $this->models->isMl($active) && $engine === 'http') {
            $remote = $this->remote($active, $featureSets);
            if ($remote !== null) {
                return $remote;
            }
        }

        // 3. Cadangan: Model Aturan PHP
        return $this->rule($featureSets);
    }

    public function assess(array $features, array $courseProjections = []): array
    {
        return $this->assessMany([$features], [$courseProjections])[0];
    }

    private function rule(array $featureSets): array
    {
        $version = $this->models->ruleModel() ?? $this->models->active();
        $thresholds = ModelService::thresholdsOf($version);

        return array_map(function (array $f) use ($version, $thresholds) {
            $score = RiskModel::score($f);

            return [
                'score' => $score,
                'level' => RiskModel::classify($score, $thresholds),
                'factors' => RiskModel::factors($f),
                'model_version_id' => $version?->id,
                'explanation_unavailable' => false,
            ];
        }, $featureSets);
    }

    /** @return list<array>|null null bila layanan gagal atau jawabannya tidak utuh */
    private function remote(ModelVersion $version, array $featureSets): ?array
    {
        $instances = array_map(fn (array $f) => $this->normalizeInstance($f), $featureSets);

        try {
            $results = $this->models->post('/predict', [
                'version' => $version->params['artifact'] ?? $version->version,
                'instances' => $instances,
            ], (int) config('risk.service_timeout', 3))->throw()->json('results');
        } catch (Throwable $e) {
            Log::warning('Layanan model tidak menjawab, memakai model aturan: '.$e->getMessage());

            return null;
        }

        if (! is_array($results) || count($results) !== count($featureSets)) {
            return null;
        }

        $thresholds = ModelService::thresholdsOf($version);
        $out = [];

        foreach (array_values($results) as $i => $r) {
            if (! in_array($r['level'] ?? null, RiskModel::LEVELS, true) || ! is_numeric($r['score'] ?? null)) {
                return null;
            }

            $f = $featureSets[$i];
            $mlScore = round((float) $r['score'], 2);

            $finalScore = $this->blendWithProjectionAndDpa($mlScore, $f);
            $finalLevel = isset($f['dpa_activity_points']) || ! empty($f['is_projection'])
                ? RiskModel::classify($finalScore, $thresholds)
                : (string) $r['level'];

            $hasContributions = isset($r['contributions']) && is_array($r['contributions']);
            $points = $hasContributions ? $this->points($r['contributions'], $f) : [];

            $out[] = [
                'score' => $finalScore,
                'level' => $finalLevel,
                'factors' => $hasContributions
                    ? RiskModel::explain($f, $points)
                    : [],
                'model_version_id' => $version->id,
                'explanation_unavailable' => ! $hasContributions,
            ];
        }

        return $out;
    }

    /** @return array<string,float|int> */
    private function normalizeInstance(array $f): array
    {
        $att = (float) ($f['attendance'] ?? 85.0);
        $ips = (float) ($f['ips'] ?? 3.0);
        $failedRatio = (float) ($f['failed_ratio'] ?? 0.0);

        return [
            'attendance' => round($att, 2),
            'min_attendance' => round((float) ($f['min_attendance'] ?? $att), 2),
            'low_attendance_courses' => (int) ($f['low_attendance_courses'] ?? ($att < RiskModel::MIN_ATTENDANCE_LIMIT ? 1 : 0)),
            'attendance_delta' => round((float) ($f['attendance_delta'] ?? 0.0), 2),
            'ips' => round($ips, 2),
            'ipk' => round((float) ($f['ipk'] ?? $ips), 2),
            'ips_delta' => round((float) ($f['ips_delta'] ?? 0.0), 2),
            'failed_ratio' => round($failedRatio, 4),
            'repeat_courses' => (int) ($f['repeat_courses'] ?? ($failedRatio >= 0.25 ? 2 : ($failedRatio > 0 ? 1 : 0))),
            'credits_delta' => (int) ($f['credits_delta'] ?? 0),
        ];
    }

    private function blendWithProjectionAndDpa(float $mlScore, array $f): float
    {
        $hasDpa = ($f['dpa_activity_points'] ?? null) !== null || ($f['dpa_thesis_points'] ?? null) !== null;
        $isProjection = ! empty($f['is_projection']);

        if (! $hasDpa && ! $isProjection) {
            return $mlScore;
        }

        $ruleScore = RiskModel::score($f);

        return round(min(RiskModel::MAX_SCORE, max(0.0, 0.70 * $mlScore + 0.30 * $ruleScore)), 2);
    }

    private function points(array $contributions, array $f): array
    {
        $points = [];
        foreach (self::FEATURES as $feature) {
            if (isset($contributions[$feature])) {
                $points[$feature] = round((float) $contributions[$feature], 2);
            }
        }

        if (isset($f['dpa_activity_points']) || isset($f['dpa_thesis_points'])) {
            $ruleParts = RiskModel::parts($f);
            if (($ruleParts['dpa_assessment'] ?? 0.0) > 0.0) {
                $points['dpa_assessment'] = $ruleParts['dpa_assessment'];
            }
        }

        return $points;
    }
}