<?php

namespace App\Services;

use App\Models\AcademicRecord;
use App\Models\HistoricalDataset;
use App\Models\HistoricalRecord;
use App\Models\ModelVersion;
use App\Support\RiskModel;
use App\Support\ThresholdTrainer;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/** Proses 7.0 dan akses model aktif (D5): melatih, membandingkan, dan mengaktifkan versi model (10 variabel inti). */
class ModelService
{
    private const ALGORITHMS = [
        'random_forest' => 'Random Forest + SHAP',
        'xgboost' => 'XGBoost + SHAP',
    ];

    private ?ModelVersion $cached = null;

    private bool $loaded = false;

    public function active(): ?ModelVersion
    {
        if (! $this->loaded) {
            $this->cached = ModelVersion::where('is_active', true)->latest('id')->first();
            $this->loaded = true;
        }

        return $this->cached;
    }

    public function isMl(ModelVersion $version): bool
    {
        return ($version->params['engine'] ?? 'rule') === 'ml';
    }

    /** Model aturan untuk perhitungan cadangan: versi aktif bila berupa aturan, jika tidak versi aturan terbaru yang lolos target. */
    public function ruleModel(): ?ModelVersion
    {
        $active = $this->active();
        if ($active && ! $this->isMl($active)) {
            return $active;
        }

        return ModelVersion::where('meets_target', true)->orderByDesc('id')->get()
            ->first(fn (ModelVersion $v) => ! $this->isMl($v));
    }

    /** @return array{sedang:float,tinggi:float} */
    public static function thresholdsOf(?ModelVersion $version): array
    {
        $p = $version?->params;

        return [
            'sedang' => (float) ($p['sedang'] ?? RiskModel::DEFAULT_THRESHOLDS['sedang']),
            'tinggi' => (float) ($p['tinggi'] ?? RiskModel::DEFAULT_THRESHOLDS['tinggi']),
        ];
    }

    /**
     * Latih versi baru (belum aktif) pada satu dataset historis dengan 10 variabel inti.
     *
     * @throws InvalidArgumentException bila data tidak layak dilatih
     */
    public function train(HistoricalDataset $dataset): ModelVersion
    {
        $rows = $dataset->records()->orderBy('id')->get()->map(function (HistoricalRecord $r) {
            $att = (float) $r->attendance;
            $ips = (float) $r->ips;
            $failed = (float) $r->failed_ratio;

            return [
                'attendance' => $att,
                'min_attendance' => $r->min_attendance !== null ? (float) $r->min_attendance : max(0.0, $att - 5.0),
                'low_attendance_courses' => $r->low_attendance_courses !== null ? (int) $r->low_attendance_courses : ($att < 75.0 ? 1 : 0),
                'attendance_delta' => $r->attendance_delta !== null ? (float) $r->attendance_delta : 0.0,
                'ips' => $ips,
                'ipk' => $r->ipk !== null ? (float) $r->ipk : $ips,
                'ips_delta' => (float) $r->ips_delta,
                'failed_ratio' => $failed,
                'repeat_courses' => $r->repeat_courses !== null ? (int) $r->repeat_courses : ($failed >= 0.25 ? 2 : ($failed > 0 ? 1 : 0)),
                'credits_delta' => $r->credits_delta !== null ? (int) $r->credits_delta : 0,
                'label' => $r->label,
            ];
        })->all();

        $nextNumber = (int) ModelVersion::max('id') + 1;
        $name = (string) $nextNumber;

        // C3: Selalu lakukan pengacakan deterministik termasuk pada training pertama.
        $seed = crc32('version-seed-'.$name.'-'.$dataset->id);
        [$trainRows, $testRows] = ThresholdTrainer::split($rows, $seed);
        $rule = ThresholdTrainer::fitOn($trainRows, $testRows);

        $ml = config('risk.engine') === 'http' ? $this->trainRemote('ml-v'.$name, $trainRows, $testRows) : null;

        if ($ml === null) {
            $version = 'aturan-v'.$name;
            $algorithm = 'Aturan berbobot (10 variabel inti), ambang dikalibrasi';
            $params = $rule['params'] + ['engine' => 'rule'];
            $metrics = $rule['metrics'] + [
                'dataset' => $dataset->name,
                'is_synthetic' => (bool) ($dataset->is_synthetic ?? false),
            ];
        } else {
            $version = $ml['version'];
            $algorithm = self::ALGORITHMS[$ml['algorithm']] ?? $ml['algorithm'];
            $params = $ml['params'] + ['engine' => 'ml', 'artifact' => $ml['version'], 'algorithm' => $ml['algorithm']];
            $metrics = $ml['metrics'] + [
                'dataset' => $dataset->name,
                'is_synthetic' => (bool) ($dataset->is_synthetic ?? false),
                'candidates' => $ml['candidates'] ?? [],
                'feature_importance' => $ml['feature_importance'] ?? [],
                'baseline' => ['macro_f1' => $rule['metrics']['macro_f1'], 'recall_high' => $rule['metrics']['recall_high']],
            ];
        }

        $meets = $metrics['macro_f1'] >= config('risk.target_macro_f1') && $metrics['recall_high'] >= config('risk.target_recall_high');

        return ModelVersion::create([
            'version' => $version,
            'algorithm' => $algorithm,
            'params' => $params,
            'macro_f1' => $metrics['macro_f1'],
            'recall_high' => $metrics['recall_high'],
            'metrics' => $metrics,
            'train_rows' => $rule['train_rows'],
            'test_rows' => $rule['test_rows'],
            'meets_target' => $meets,
            'is_active' => false,
            'trained_at' => now(),
        ]);
    }

    public function activate(ModelVersion $version): void
    {
        if (! $version->meets_target) {
            throw new RuntimeException('Metrik versi ini di bawah target. Model yang aktif tetap dipakai.');
        }
        DB::transaction(function () use ($version) {
            ModelVersion::where('is_active', true)->update(['is_active' => false]);
            $version->update(['is_active' => true]);
        });
        $this->loaded = false;

        $studentRefs = AcademicRecord::query()->distinct()->pluck('student_ref')->all();
        if ($studentRefs !== []) {
            $riskService = app(RiskService::class);
            foreach ($studentRefs as $ref) {
                $riskService->refresh((string) $ref);
            }
        }
    }

    /** Kirim permintaan REST internal ke layanan model. */
    public function post(string $path, array $payload, int $timeout): Response
    {
        $baseUrl = rtrim(trim((string) config('risk.service_url')), '/');

        if ($baseUrl !== '' && ! str_starts_with($baseUrl, 'http://') && ! str_starts_with($baseUrl, 'https://')) {
            $baseUrl = 'https://'.$baseUrl;
        }

        if ($baseUrl === '' || ! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('RISK_SERVICE_URL belum siap atau tidak valid: '.$baseUrl);
        }

        $token = trim((string) config('risk.service_token'));
        $cleanPath = '/'.ltrim($path, '/');

        return Http::timeout($timeout)->acceptJson()
            ->withHeaders($token !== '' ? ['X-Service-Token' => $token] : [])
            ->post($baseUrl.$cleanPath, $payload);
    }

    /** @return array|null null bila layanan tidak tersedia (model aturan dipakai) */
    private function trainRemote(string $version, array $train, array $test): ?array
    {
        try {
            $res = $this->post('/train', ['version' => $version, 'train' => $train, 'test' => $test], (int) config('risk.train_timeout', 120));
        } catch (Throwable $e) {
            Log::warning('Layanan model tidak tersedia untuk pelatihan: '.$e->getMessage());

            return null;
        }

        if ($res->status() === 422) {
            $detail = $res->json('detail');
            throw new InvalidArgumentException(is_string($detail) ? $detail : 'Layanan model menolak data historis. Periksa isi berkas.');
        }
        $body = $res->json();
        if ($res->failed() || ! is_numeric($body['metrics']['macro_f1'] ?? null) || ! is_numeric($body['metrics']['recall_high'] ?? null)
            || ! is_numeric($body['params']['sedang'] ?? null) || ! is_numeric($body['params']['tinggi'] ?? null)
            || ! is_string($body['algorithm'] ?? null) || ($body['version'] ?? null) !== $version) {
            Log::warning('Layanan model menjawab pelatihan dengan status '.$res->status().'. Memakai model aturan.');

            return null;
        }

        return $body;
    }
}
