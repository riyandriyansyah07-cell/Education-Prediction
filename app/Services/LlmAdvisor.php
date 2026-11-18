<?php

namespace App\Services;

use App\Support\Fmt;
use App\Support\RiskModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Layanan AI Utama (Gemini / DeepSeek):
 * 1. assessRisk(): Bertindak sebagai AI utama penentu parameter risiko (skor 0-10, level risiko,
 *    faktor penyebab beserta poin kontribusinya, dan saran tindak lanjut) menggunakan Structured JSON Output
 *    dengan temperature 0.0 serta guardrail aturan akademik UHAMKA.
 * 2. advise(): Menyusun saran tindak lanjut saja apabila perhitungan skor dilakukan oleh ml-service/aturan.
 *
 * Keamanan & Privasi: Tidak pernah mengirim NIM, nama mahasiswa, maupun nama mata kuliah ke API eksternal.
 */
class LlmAdvisor
{
    /**
     * Evaluasi risiko utama oleh Gemini/DeepSeek menggunakan 10 variabel inti dan ringkasan proyeksi.
     *
     * @param  array<string,mixed>  $features
     * @param  list<array<string,mixed>>  $courseProjections
     * @return array{
     *   score:float,
     *   level:string,
     *   factors:list<array{key:string,label:string,detail:string,points:float,share:float}>,
     *   suggestions:list<string>,
     *   llm_generated:bool
     * }|null
     */
    public function assessRisk(array $features, array $courseProjections = []): ?array
    {
        $provider = strtolower(trim((string) config('services.llm.provider', 'gemini')));
        $apiKey = trim((string) config('services.llm.api_key', ''));
        $timeout = max(3, min(15, (int) config('services.llm.timeout', 10)));

        if ($provider === '' || $provider === 'none' || $apiKey === '') {
            return null;
        }

        $prompt = $this->buildAssessmentPrompt($features, $courseProjections);

        try {
            $rawJson = match ($provider) {
                'gemini' => $this->callGeminiJson($apiKey, $prompt, $timeout),
                'deepseek' => $this->callDeepSeekJson($apiKey, $prompt, $timeout),
                default => null,
            };

            if (! is_array($rawJson)) {
                return null;
            }

            return $this->validateAndApplyGuardrails($rawJson, $features);
        } catch (Throwable $e) {
            Log::warning("LlmAdvisor::assessRisk ({$provider}) gagal, beralih ke cadangan RiskModel: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Hasilkan daftar saran tindak lanjut saja (dipakai ketika mode engine adalah ml-service atau rule).
     *
     * @param  array<string,mixed>  $features
     * @param  list<array{key:string,label:string,detail:string,points:float,share:float}>  $factors
     * @return array{suggestions:list<string>,llm_generated:bool}
     */
    public function advise(string $level, float $score, array $features, array $factors): array
    {
        $fallback = [
            'suggestions' => RiskModel::suggestions($level, $factors),
            'llm_generated' => false,
        ];

        $provider = strtolower(trim((string) config('services.llm.provider', '')));
        $apiKey = trim((string) config('services.llm.api_key', ''));
        $timeout = max(2, min(15, (int) config('services.llm.timeout', 10)));

        if ($provider === '' || $provider === 'none' || $apiKey === '') {
            return $fallback;
        }

        $prompt = $this->buildSanitizedPrompt($level, $score, $features, $factors);

        try {
            $lines = match ($provider) {
                'gemini' => $this->callGeminiText($apiKey, $prompt, $timeout),
                'deepseek' => $this->callDeepSeekText($apiKey, $prompt, $timeout),
                default => null,
            };

            if (is_array($lines) && count($lines) >= 2) {
                return [
                    'suggestions' => array_slice($lines, 0, 4),
                    'llm_generated' => true,
                ];
            }
        } catch (Throwable $e) {
            Log::warning("LlmAdvisor::advise ({$provider}) gagal, beralih ke saran statis: ".$e->getMessage());
        }

        return $fallback;
    }

    private function buildAssessmentPrompt(array $f, array $courseProjections): string
    {
        $payload = [
            'variabel_inti' => [
                'kehadiran_rata_rata_persen' => (float) ($f['attendance'] ?? 85),
                'kehadiran_terendah_persen' => (float) ($f['min_attendance'] ?? ($f['attendance'] ?? 85)),
                'jumlah_mk_kehadiran_bawah_75' => (int) ($f['low_attendance_courses'] ?? 0),
                'tren_kehadiran_persen' => (float) ($f['attendance_delta'] ?? 0),
                'ips_berjalan' => (float) ($f['ips'] ?? 3.0),
                'ipk_kumulatif' => (float) ($f['ipk'] ?? ($f['ips'] ?? 3.0)),
                'tren_ips' => (float) ($f['ips_delta'] ?? 0),
                'rasio_sks_bawah_c' => (float) ($f['failed_ratio'] ?? 0),
                'jumlah_mk_mengulang_de' => (int) ($f['repeat_courses'] ?? 0),
                'perubahan_beban_sks' => (int) ($f['credits_delta'] ?? 0),
            ],
            'proyeksi_semester' => [
                'apakah_proyeksi_berjalan' => (bool) ($f['is_projection'] ?? false),
                'komponen_terisi_dari_4' => (int) ($f['components_count'] ?? 4),
                'poin_dpa_keaktifan' => $f['dpa_activity_points'] ?? null,
                'poin_dpa_tugas_akhir' => $f['dpa_thesis_points'] ?? null,
                'ringkasan_mk_anonim' => array_map(fn ($c) => [
                    'sks' => (int) ($c['credits'] ?? 3),
                    'proyeksi_nilai_N' => $c['projected_score'] ?? null,
                    'uas_min_untuk_C' => $c['min_uas_for_c'] ?? null,
                    'uas_min_untuk_B' => $c['min_uas_for_b'] ?? null,
                    'risiko_mk' => $c['course_risk_level'] ?? 'rendah',
                ], array_slice($courseProjections, 0, 12)),
            ],
        ];

        $jsonInput = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Kamu adalah mesin AI utama penilai risiko akademik mahasiswa FTII UHAMKA. Tugasmu mengevaluasi 10 variabel inti dan ringkasan proyeksi nilai mata kuliah anonim berikut, lalu menghitung parameter risiko akademik secara objektif.

Pedoman Penilaian UHAMKA:
1. Skala nilai huruf: A (80-100), B (68-79), C (56-67), D (45-55), E (0-44).
2. Syarat minimal kehadiran ujian adalah 75%. Jika jumlah_mk_kehadiran_bawah_75 >= 1 atau kehadiran_terendah_persen < 75, risiko TIDAK BOLEH "rendah" (minimal "sedang", atau "tinggi" jika ada faktor lain).
3. Ambang skor 0.00 sampai 10.00:
   - "rendah": skor < 2.50
   - "sedang": skor 2.50 sampai 4.99
   - "tinggi": skor >= 5.00
4. Kunci faktor yang valid untuk array factors: "attendance", "min_attendance", "low_attendance_courses", "attendance_delta", "ips", "ipk", "ips_delta", "failed_ratio", "repeat_courses", "credits_delta", "dpa_assessment".

Data Akademik Anonim Mahasiswa:
{$jsonInput}

Kembalikan HANYA objek JSON valid (tanpa teks pembuka/penutup) dengan struktur persis berikut:
{
  "score": 0.0,
  "level": "rendah | sedang | tinggi",
  "factors": [
    {
      "key": "salah_satu_kunci_di_atas",
      "label": "Nama faktor singkat bahasa Indonesia",
      "detail": "Penjelasan 1 kalimat menyebut angka fakta dari data di atas",
      "points": 0.0,
      "share": 0.0
    }
  ],
  "suggestions": [
    "Saran langkah konkret 1",
    "Saran langkah konkret 2",
    "Saran langkah konkret 3"
  ]
}
PROMPT;
    }

    private function validateAndApplyGuardrails(array $raw, array $features): ?array
    {
        if (! isset($raw['score']) || ! is_numeric($raw['score'])) {
            return null;
        }

        $score = round(min(RiskModel::MAX_SCORE, max(0.0, (float) $raw['score'])), 2);
        $level = strtolower(trim((string) ($raw['level'] ?? '')));
        if (! in_array($level, RiskModel::LEVELS, true)) {
            $level = RiskModel::classify($score);
        }

        // Guardrail aturan akademik: kehadiran di bawah 75% tidak boleh diklasifikasikan rendah
        $lowAtt = (int) ($features['low_attendance_courses'] ?? 0);
        $minAtt = (float) ($features['min_attendance'] ?? 100.0);
        if (($lowAtt > 0 || $minAtt < RiskModel::MIN_ATTENDANCE_LIMIT) && $level === 'rendah') {
            $level = 'sedang';
            $score = max($score, 3.0);
        }

        // Selaraskan level dengan ambang skor
        $level = RiskModel::classify($score);

        $factors = [];
        if (isset($raw['factors']) && is_array($raw['factors'])) {
            foreach ($raw['factors'] as $item) {
                if (! is_array($item) || empty($item['label'])) {
                    continue;
                }
                $pts = round(max(0.0, (float) ($item['points'] ?? 0.0)), 2);
                if ($pts <= 0.0) {
                    continue;
                }
                $factors[] = [
                    'key' => (string) ($item['key'] ?? 'ips'),
                    'label' => (string) $item['label'],
                    'detail' => (string) ($item['detail'] ?? ''),
                    'points' => $pts,
                    'share' => round(max(0.0, min(100.0, (float) ($item['share'] ?? 0.0))), 1),
                ];
            }
        }

        if ($factors === [] && $score > 0.0) {
            $factors = RiskModel::factors($features);
        } else {
            // Normalisasi persentase share agar totalnya 100%
            $totalPts = array_sum(array_column($factors, 'points'));
            if ($totalPts > 0.0) {
                foreach ($factors as &$fac) {
                    $fac['share'] = round(($fac['points'] / $totalPts) * 100, 1);
                }
                unset($fac);
            }
            usort($factors, fn ($a, $b) => $b['points'] <=> $a['points']);
        }

        $suggestions = [];
        if (isset($raw['suggestions']) && is_array($raw['suggestions'])) {
            foreach ($raw['suggestions'] as $s) {
                $clean = trim(str_replace(['**', '__'], '', (string) $s));
                if (mb_strlen($clean) >= 12) {
                    $suggestions[] = $clean;
                }
            }
        }
        if ($suggestions === []) {
            $suggestions = RiskModel::suggestions($level, $factors);
        }

        return [
            'score' => $score,
            'level' => $level,
            'factors' => array_slice($factors, 0, 5),
            'suggestions' => array_slice(array_values(array_unique($suggestions)), 0, 4),
            'llm_generated' => true,
        ];
    }

    private function callGeminiJson(string $apiKey, string $prompt, int $timeout): ?array
    {
        $model = trim((string) config('services.llm.model', 'gemini-2.5-flash')) ?: 'gemini-2.5-flash';
        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            urlencode($model),
            urlencode($apiKey)
        );

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->post($endpoint, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature' => 0.0,
                    'responseMimeType' => 'application/json',
                    'maxOutputTokens' => 900,
                ],
            ])
            ->throw();

        $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');

        return $this->decodeJson($text);
    }

    private function callDeepSeekJson(string $apiKey, string $prompt, int $timeout): ?array
    {
        $model = trim((string) config('services.llm.model', 'deepseek-chat')) ?: 'deepseek-chat';
        $baseUrl = rtrim((string) config('services.llm.base_url', 'https://api.deepseek.com'), '/');

        $response = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->post($baseUrl.'/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Kamu adalah mesin evaluasi risiko akademik. Kembalikan hanya JSON valid.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.0,
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => 900,
            ])
            ->throw();

        $text = (string) data_get($response->json(), 'choices.0.message.content', '');

        return $this->decodeJson($text);
    }

    private function decodeJson(string $text): ?array
    {
        $cleaned = trim($text);
        $cleaned = (string) preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $cleaned);
        $decoded = json_decode($cleaned, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function buildSanitizedPrompt(string $level, float $score, array $features, array $factors): string
    {
        $factorLines = [];
        foreach (array_slice($factors, 0, 4) as $f) {
            $factorLines[] = sprintf(
                '- %s (+%s poin, porsi %s%%): %s',
                $f['label'] ?? 'Faktor',
                Fmt::num((float) ($f['points'] ?? 0), 2),
                Fmt::num((float) ($f['share'] ?? 0), 1),
                $f['detail'] ?? ''
            );
        }
        $factorText = $factorLines !== []
            ? implode("\n", $factorLines)
            : '- Tidak ada faktor risiko yang menonjol pada semester ini.';

        $att = Fmt::pct((float) ($features['attendance'] ?? 0));
        $ips = Fmt::num((float) ($features['ips'] ?? 0));
        $ipk = Fmt::num((float) ($features['ipk'] ?? ($features['ips'] ?? 0)));
        $lowAtt = (int) ($features['low_attendance_courses'] ?? 0);

        return <<<PROMPT
Kamu adalah asisten akademik perguruan tinggi di Indonesia. Tugasmu menyusun 3 sampai 4 butir saran langkah belajar yang netral, praktis, dan tidak menghakimi untuk mahasiswa berdasarkan indikator numerik anonim berikut:

- Tingkat risiko saat ini: {$level} (skor {$score} dari 10)
- Kehadiran rata-rata: {$att} ({$lowAtt} mata kuliah di bawah batas kehadiran 75%)
- IPS semester berjalan: {$ips} | IPK kumulatif: {$ipk}
- Faktor penyumbang utama:
{$factorText}

Tulis tepat 3 atau 4 baris saran dalam bahasa Indonesia tanpa markdown tebal (**). Pisahkan tiap butir dengan baris baru.
PROMPT;
    }

    /** @return list<string>|null */
    private function callGeminiText(string $apiKey, string $prompt, int $timeout): ?array
    {
        $model = trim((string) config('services.llm.model', 'gemini-2.5-flash')) ?: 'gemini-2.5-flash';
        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            urlencode($model),
            urlencode($apiKey)
        );

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->post($endpoint, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 350],
            ])
            ->throw();

        return $this->parseLines((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));
    }

    /** @return list<string>|null */
    private function callDeepSeekText(string $apiKey, string $prompt, int $timeout): ?array
    {
        $model = trim((string) config('services.llm.model', 'deepseek-chat')) ?: 'deepseek-chat';
        $baseUrl = rtrim((string) config('services.llm.base_url', 'https://api.deepseek.com'), '/');

        $response = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->post($baseUrl.'/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Kamu adalah konselor akademik perguruan tinggi yang menyusun saran belajar singkat, objektif, dan suportif.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 350,
            ])
            ->throw();

        return $this->parseLines((string) data_get($response->json(), 'choices.0.message.content', ''));
    }

    /** @return list<string> */
    private function parseLines(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($raw)) ?: [];
        $cleaned = [];
        foreach ($lines as $line) {
            $line = trim((string) preg_replace('/^(\d+[\.\)]\s*|[-*•]\s*)/u', '', trim($line)));
            $line = str_replace(['**', '__'], '', $line);
            if (mb_strlen($line) >= 18) {
                $cleaned[] = $line;
            }
        }

        return array_values(array_unique($cleaned));
    }
}