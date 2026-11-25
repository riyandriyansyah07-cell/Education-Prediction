<?php

namespace Tests\Feature;

use App\Models\AccountTicket;
use App\Models\ModelVersion;
use App\Models\RiskResult;
use App\Models\User;
use App\Services\Pseudonym;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function seedDashboardData(): array
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

        $dpa = User::create([
            'name' => 'Erizal, S.Kom., M.Kom.',
            'username' => '0312058801',
            'role' => User::ROLE_DPA,
            'password' => 'password123',
            'is_active' => true,
        ]);

        $mhsTI23 = User::create([
            'name' => 'Aulia Azzahra',
            'username' => '2303045003',
            'program_studi' => 'Teknik Informatika',
            'role' => User::ROLE_STUDENT,
            'password' => 'password123',
            'student_ref' => Pseudonym::ref('2303045003'),
            'dpa_id' => $dpa->id,
            'is_active' => true,
        ]);

        $mhsTE24 = User::create([
            'name' => 'Budi Santoso',
            'username' => '2403025011',
            'program_studi' => 'Teknik Elektro',
            'role' => User::ROLE_STUDENT,
            'password' => 'password123',
            'student_ref' => Pseudonym::ref('2403025011'),
            'dpa_id' => $dpa->id,
            'is_active' => true,
        ]);

        RiskResult::create([
            'student_ref' => $mhsTI23->student_ref,
            'semester' => 6,
            'level' => 'tinggi',
            'score' => 7.25,
            'features' => ['ips' => 1.95, 'attendance' => 65.0],
            'factors' => [['key' => 'attendance', 'label' => 'Kehadiran di bawah 75%', 'points' => 3.5]],
            'suggestions' => ['Segera temui DPA.'],
            'data_as_of' => now()->toDateString(), 'model_version_id' => $model->id,
        ]);

        RiskResult::create([
            'student_ref' => $mhsTE24->student_ref,
            'semester' => 4,
            'level' => 'rendah',
            'score' => 1.10,
            'features' => ['ips' => 3.65, 'attendance' => 94.0],
            'factors' => [],
            'suggestions' => ['Pertahankan performa.'],
            'data_as_of' => now()->toDateString(), 'model_version_id' => $model->id,
        ]);

        return [$admin, $dpa, $mhsTI23, $mhsTE24];
    }

    public function test_hanya_admin_yang_dapat_mengakses_dasbor_dan_beranda_mengarah_ke_dasbor(): void
    {
        [$admin, $dpa, $mhs] = $this->seedDashboardData();

        $this->actingAs($mhs)->get(route('admin.dasbor'))->assertForbidden();
        $this->actingAs($dpa)->get(route('admin.dasbor'))->assertForbidden();

        $this->actingAs($admin)->get(route('beranda'))->assertRedirect(route('admin.dasbor'));

        $this->actingAs($admin)->get(route('admin.dasbor'))
            ->assertOk()
            ->assertSee('Dasbor Ringkasan Akademik')
            ->assertSee('Teknik Informatika')
            ->assertSee('Teknik Elektro')
            ->assertSee('Angkatan 2023')
            ->assertSee('Angkatan 2024')
            ->assertSee('Aulia Azzahra')
            ->assertSee('Budi Santoso')
            ->assertSee('Erizal, S.Kom., M.Kom.');
    }

    public function test_filter_dasbor_admin_dan_ekspor_csv(): void
    {
        [$admin] = $this->seedDashboardData();

        $this->actingAs($admin)->get(route('admin.dasbor', [
            'prodi' => 'Teknik Informatika',
            'angkatan' => '2023',
            'tingkat' => 'tinggi',
        ]))
            ->assertOk()
            ->assertSee('Aulia Azzahra')
            ->assertDontSee('Budi Santoso');

        $csvResponse = $this->actingAs($admin)->get(route('admin.dasbor.ekspor', ['prodi' => 'Teknik Informatika']));
        $csvResponse->assertOk();
        $content = $csvResponse->streamedContent();
        $this->assertStringContainsString('Aulia Azzahra', $content);
        $this->assertStringContainsString('Teknik Informatika', $content);
        $this->assertStringNotContainsString('Budi Santoso', $content);
    }

    public function test_admin_dapat_menyimpan_program_studi_mahasiswa(): void
    {
        [$admin, $dpa] = $this->seedDashboardData();

        $this->actingAs($admin)->post(route('admin.akun.store'), [
            'name' => 'Mahasiswa Baru STI',
            'username' => '2503045099',
            'program_studi' => AccountTicket::PROGRAMS[3],
            'role' => User::ROLE_STUDENT,
            'password' => 'password123',
            'dpa_id' => $dpa->id,
        ])->assertRedirect(route('admin.akun'));

        $this->assertDatabaseHas('users', [
            'username' => '2503045099',
            'program_studi' => AccountTicket::PROGRAMS[3],
        ]);
    }
}
