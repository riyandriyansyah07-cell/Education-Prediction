<?php

namespace Tests\Feature;

use App\Models\Consent;
use App\Models\StudentSetting;
use App\Models\User;
use App\Support\EnrichmentCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SkpiPkmTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_update_four_skpi_requirements_and_pkm_stage(): void
    {
        $dpa = User::create([
            'name' => 'DPA SKPI',
            'username' => 'dpa.skpi',
            'email' => 'dpa.skpi@uhamka.ac.id',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_DPA,
            'is_active' => true,
        ]);

        $student = User::create([
            'name' => 'Mahasiswa SKPI',
            'username' => '2303045099',
            'email' => 'mhs.skpi@uhamka.ac.id',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_STUDENT,
            'dpa_id' => $dpa->id,
            'student_ref' => hash('sha256', 'skpi-test-ref'),
            'is_active' => true,
        ]);

        Consent::create([
            'student_ref' => $student->student_ref,
            'dpa_id' => $dpa->id,
            'granted' => true,

        ]);

        // 1. Tahap Fakultas belum diakui SKPI
        $this->actingAs($student)
            ->post(route('mhs.pengayaan.skpi'), [
                'skpi_it_status' => 'selesai',
                'skpi_it_provider' => 'BNSP',
                'skpi_baps_status' => 'selesai',
                'skpi_pkm_stage' => 'fakultas',
                'skpi_toefl_status' => 'proses',
                'skpi_toefl_score' => null,
            ])
            ->assertRedirect(route('mhs.pengayaan'));

        $setting = StudentSetting::where('student_ref', $student->student_ref)->firstOrFail();
        $progress = EnrichmentCatalog::evaluateProgress(collect(), 7, $setting);

        $this->assertSame('proses', $progress['pkm_status']);
        $this->assertStringContainsString('Fakultas', $progress['pkm_label']);
        $this->assertSame(2, $progress['skpi']['completed']);

        // 2. Lolos Tahap Universitas diakui SKPI + TOEFL UHAMKA selesai => 4/4 SKPI tuntas
        $this->actingAs($student)
            ->post(route('mhs.pengayaan.skpi'), [
                'skpi_it_status' => 'selesai',
                'skpi_it_provider' => 'Mahira',
                'skpi_baps_status' => 'selesai',
                'skpi_pkm_stage' => 'universitas',
                'skpi_toefl_status' => 'selesai',
                'skpi_toefl_score' => 510,
            ])
            ->assertRedirect(route('mhs.pengayaan'));

        $setting->refresh();
        $progressUniv = EnrichmentCatalog::evaluateProgress(collect(), 7, $setting);

        $this->assertSame('selesai', $progressUniv['pkm_status']);
        $this->assertSame(4, $progressUniv['skpi']['completed']);
        $this->assertTrue($progressUniv['skpi']['all_done']);

        // 3. Tampilan DPA menampilkan progres 4/4 SKPI dan lembaga Mahira
        $this->actingAs($dpa)
            ->get(route('dpa.bimbingan.show', $student))
            ->assertOk()
            ->assertSee('4/4 SKPI')
            ->assertSee('Mahira')
            ->assertSee('TOEFL UHAMKA');
    }
}
