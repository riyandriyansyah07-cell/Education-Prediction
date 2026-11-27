<?php

namespace Tests\Feature;

use App\Models\AiValidation;
use App\Models\ModelVersion;
use App\Models\RiskResult;
use App\Models\User;
use App\Services\Pseudonym;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidatorAuditTest extends TestCase
{
    use RefreshDatabase;

    private function seedAuditUsersAndResult(): array
    {
        $model = ModelVersion::create([
            'version' => 'aturan-v1',
            'algorithm' => 'Aturan berbobot',
            'params' => ['sedang' => 2.5, 'tinggi' => 5.0, 'engine' => 'rule'],
            'meets_target' => true,
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin FTII',
            'username' => 'admin',
            'role' => User::ROLE_ADMIN,
            'password' => 'password123',
            'is_active' => true,
        ]);

        $validator = User::create([
            'name' => 'Tim Penjamin Mutu AI',
            'username' => 'validator',
            'role' => User::ROLE_VALIDATOR,
            'password' => 'password123',
            'is_active' => true,
        ]);

        $student = User::create([
            'name' => 'Aulia Azzahra',
            'username' => '2303045003',
            'program_studi' => 'Teknik Informatika',
            'role' => User::ROLE_STUDENT,
            'password' => 'password123',
            'student_ref' => Pseudonym::ref('2303045003'),
            'is_active' => true,
        ]);

        $result = RiskResult::create([
            'student_ref' => $student->student_ref,
            'semester' => 6,
            'level' => 'tinggi',
            'score' => 7.20,
            'features' => [
                'attendance' => 88.0,
                'ips' => 3.20,
                'ipk' => 3.15,
                'failed_ratio' => 0.0,
                'ips_delta' => 0.10,
            ],
            'factors' => [['key' => 'ips', 'label' => 'Anomali skor AI', 'points' => 4.0]],
            'suggestions' => ['Cek ulang data.'],
            'llm_generated' => true,
            'data_as_of' => now()->toDateString(),
            'model_version_id' => $model->id,
        ]);

        return [$admin, $validator, $student, $result];
    }

    public function test_hanya_validator_dapat_mengakses_panel_audit_dan_beranda_mengarah_ke_audit(): void
    {
        [$admin, $validator, $student] = $this->seedAuditUsersAndResult();

        $this->actingAs($student)->get(route('validator.audit'))->assertForbidden();
        $this->actingAs($admin)->get(route('validator.audit'))->assertForbidden();

        $this->actingAs($validator)->get(route('beranda'))->assertRedirect(route('validator.audit'));

        $this->actingAs($validator)->get(route('validator.audit'))
            ->assertOk()
            ->assertSee('Audit & Validasi Hasil AI Gemini')
            ->assertSee('Aulia Azzahra')
            ->assertSee('Gemini AI');
    }

    public function test_validator_dapat_menandai_sesuai_dan_perlu_koreksi_serta_ekspor_csv(): void
    {
        [, $validator, , $result] = $this->seedAuditUsersAndResult();

        // 1. Tandai Sesuai
        $this->actingAs($validator)->post(route('validator.audit.store', $result), [
            'verdict' => AiValidation::VERDICT_SESUAI,
            'notes' => 'Analisis sesuai dengan data.',
        ])->assertRedirect();

        $this->assertDatabaseHas('ai_validations', [
            'risk_result_id' => $result->id,
            'validator_id' => $validator->id,
            'verdict' => AiValidation::VERDICT_SESUAI,
            'override_applied' => false,
        ]);

        // 2. Ubah menjadi Perlu Koreksi + terapkan override ke hasil mahasiswa
        $this->actingAs($validator)->post(route('validator.audit.store', $result), [
            'verdict' => AiValidation::VERDICT_KOREKSI,
            'expected_level' => 'rendah',
            'notes' => 'IPS 3.20 dan kehadiran 88% seharusnya risiko rendah.',
            'apply_override' => 1,
        ])->assertRedirect();

        $this->assertSame('rendah', $result->fresh()->level);
        $this->assertDatabaseHas('ai_validations', [
            'risk_result_id' => $result->id,
            'verdict' => AiValidation::VERDICT_KOREKSI,
            'expected_level' => 'rendah',
            'override_applied' => true,
        ]);

        // 3. Ekspor laporan CSV
        $csv = $this->actingAs($validator)->get(route('validator.audit.ekspor'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Aulia Azzahra', $csv);
        $this->assertStringContainsString('Perlu Koreksi', $csv);
    }

    public function test_admin_dapat_membuat_akun_validator_ai(): void
    {
        [$admin] = $this->seedAuditUsersAndResult();

        $this->actingAs($admin)->post(route('admin.akun.store'), [
            'name' => 'Dr. Penjamin Mutu',
            'username' => 'validator2',
            'role' => User::ROLE_VALIDATOR,
            'password' => 'password123',
        ])->assertRedirect(route('admin.akun'));

        $this->assertDatabaseHas('users', [
            'username' => 'validator2',
            'role' => User::ROLE_VALIDATOR,
        ]);
    }

    public function test_validator_mendukung_mode_tabel_paginasi_ekspor_xlsx_dan_print(): void
    {
        [, $validator] = $this->seedAuditUsersAndResult();

        // Mode tabel laporan + paginasi & sorting
        $this->actingAs($validator)->get(route('validator.audit', [
            'tampilan' => 'tabel',
            'sort' => 'delta',
            'per_page' => 10,
            'page' => 1,
        ]))
            ->assertOk()
            ->assertSee('Aulia Azzahra')
            ->assertSee('Mode Tabel Laporan');

        // Ekspor Excel (.xlsx) 2-sheet
        $xlsx = $this->actingAs($validator)->get(route('validator.audit.ekspor', ['format' => 'xlsx']));
        $xlsx->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $xlsx->headers->get('Content-Type')
        );

        // Pratinjau cetak / PDF
        $this->actingAs($validator)->get(route('validator.audit.ekspor', ['format' => 'print']))
            ->assertOk()
            ->assertSee('Laporan Audit')
            ->assertSee('Aulia Azzahra');
    }
}
