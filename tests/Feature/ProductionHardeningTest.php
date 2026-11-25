<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LlmAdvisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_llm_guardrail_filters_hallucinated_factors_and_records_healthy_status(): void
    {
        Cache::forget(LlmAdvisor::HEALTH_CACHE_KEY);

        config([
            'services.llm.provider' => 'gemini',
            'services.llm.api_key' => 'test-key-123',
            'services.llm.model' => 'gemini-3.1-flash-lite',
            'services.llm.timeout' => 25,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'score' => 0.85,
                                'level' => 'rendah',
                                'encouragement' => 'Performa akademikmu sangat baik dan konsisten.',
                                'factors' => [
                                    ['key' => 'failed_ratio', 'label' => 'Rasio Kegagalan', 'detail' => 'Halusinasi AI', 'points' => 0.5, 'share' => 60],
                                    ['key' => 'ipk', 'label' => 'Indeks Prestasi Kumulatif', 'detail' => 'Halusinasi AI', 'points' => 0.35, 'share' => 40],
                                ],
                                'suggestions' => ['Pertahankan pola belajar yang sudah baik untuk semester depan.'],
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $advisor = new LlmAdvisor;
        $result = $advisor->assessRisk([
            'ips' => 3.65,
            'ipk' => 3.65,
            'attendance' => 99.0,
            'failed_ratio' => 0.0,
            'ips_delta' => 0.0,
            'enrichment_risk_points' => 0.0,
        ]);

        $this->assertNotNull($result);
        $this->assertTrue($result['llm_generated']);
        $this->assertSame([], $result['factors']);

        $health = LlmAdvisor::healthStatus();
        $this->assertSame('healthy', $health['status']);
        $this->assertSame(0, $health['consecutive_failures']);
        $this->assertSame('gemini-3.1-flash-lite', $health['model']);
    }

    public function test_llm_failure_records_degraded_health_and_shows_on_admin_dashboard(): void
    {
        Cache::forget(LlmAdvisor::HEALTH_CACHE_KEY);

        config([
            'services.llm.provider' => 'gemini',
            'services.llm.api_key' => 'test-key-123',
            'services.llm.model' => 'gemini-3.1-flash-lite',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'Model not found']], 404),
        ]);

        $advisor = new LlmAdvisor;
        $result = $advisor->assessRisk([
            'ips' => 3.2,
            'ipk' => 3.2,
            'attendance' => 90.0,
            'failed_ratio' => 0.0,
            'ips_delta' => 0.0,
        ]);

        $this->assertNull($result);

        $health = LlmAdvisor::healthStatus();
        $this->assertSame('degraded', $health['status']);
        $this->assertSame(1, $health['consecutive_failures']);
        $this->assertNotEmpty($health['last_error']);

        $admin = User::create([
            'name' => 'Admin Monitor',
            'username' => 'admin.monitor',
            'email' => 'admin.monitor@uhamka.ac.id',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dasbor'))
            ->assertOk()
            ->assertSee('AI: Gagal 1x');
    }
}
