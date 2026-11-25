<?php

namespace Tests\Feature;

use App\Mail\AccountTicketMail;
use App\Mail\ReminderMail;
use App\Models\AcademicRecord;
use App\Models\AccountTicket;
use App\Models\EnrichmentRecord;
use App\Models\ModelVersion;
use App\Models\StudentSetting;
use App\Models\User;
use App\Services\Pseudonym;
use App\Services\RiskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuditFixesTest extends TestCase
{
    use RefreshDatabase;

    private function seedRuleAndUsers(): array
    {
        ModelVersion::create([
            'version' => 'aturan-v1',
            'algorithm' => 'Aturan berbobot',
            'params' => ['sedang' => 2.5, 'tinggi' => 5.0, 'engine' => 'rule'],
            'meets_target' => true,
            'is_active' => true,
        ]);

        $student = User::create([
            'name' => 'Mahasiswa Uji',
            'username' => '2303045999',
            'email' => 'mhs@example.com',
            'role' => User::ROLE_STUDENT,
            'password' => 'password123',
            'student_ref' => Pseudonym::ref('2303045999'),
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
            'password' => 'password123',
            'is_active' => true,
        ]);

        return [$student, $admin];
    }

    public function test_k1_sinkronisasi_enrichment_tidak_inflasi_cd_dan_e_tidak_berstatus_dikonversi(): void
    {
        [$student] = $this->seedRuleAndUsers();

        AcademicRecord::create([
            'student_ref' => $student->student_ref,
            'semester' => 5,
            'course_code' => 'EP01',
            'course_name' => 'Etika Profesional',
            'credits' => 2,
            'grade_point' => 2.0,
            'grade_input' => 'C',
            'attendance' => 100,
            'is_official_khs' => true,
        ]);

        AcademicRecord::create([
            'student_ref' => $student->student_ref,
            'semester' => 5,
            'course_code' => 'EP02',
            'course_name' => 'Pemecahan Masalah',
            'credits' => 2,
            'grade_point' => 1.0,
            'grade_input' => 'D',
            'attendance' => 100,
            'is_official_khs' => true,
        ]);

        AcademicRecord::create([
            'student_ref' => $student->student_ref,
            'semester' => 5,
            'course_code' => 'EP03',
            'course_name' => 'Penelitian',
            'credits' => 2,
            'grade_point' => 0.0,
            'grade_input' => 'E',
            'attendance' => 100,
            'is_official_khs' => true,
        ]);

        app(RiskService::class)->refresh($student->student_ref);

        $recC = EnrichmentRecord::where('student_ref', $student->student_ref)->where('course_code', 'etika-profesional')->firstOrFail();
        $this->assertSame('C', $recC->grade);
        $this->assertSame(EnrichmentRecord::STATUS_DIKONVERSI, $recC->status);

        $recD = EnrichmentRecord::where('student_ref', $student->student_ref)->where('course_code', 'pemecahan-masalah')->firstOrFail();
        $this->assertSame('D', $recD->grade);
        $this->assertSame(EnrichmentRecord::STATUS_DIKONVERSI, $recD->status);

        $recE = EnrichmentRecord::where('student_ref', $student->student_ref)->where('course_code', 'penelitian')->firstOrFail();
        $this->assertSame('E', $recE->grade);
        $this->assertSame(EnrichmentRecord::STATUS_DICICIL, $recE->status);
        $this->assertNull($recE->conversion_semester);
    }

    public function test_k2_enrichment_wajib_grade_saat_dikonversi_dan_mengizinkan_cd(): void
    {
        [$student] = $this->seedRuleAndUsers();

        // Status dikonversi tanpa grade harus ditolak (tidak diam-diam jadi 'A')
        $this->actingAs($student)->post(route('mhs.pengayaan.store'), [
            'course_code' => 'etika-profesional',
            'activity_type' => 'Magang',
            'activity_semester' => 5,
            'status' => EnrichmentRecord::STATUS_DIKONVERSI,
            'conversion_semester' => 5,
            'grade' => '',
        ])->assertSessionHasErrors('grade');

        // Nilai C harus diterima
        $this->actingAs($student)->post(route('mhs.pengayaan.store'), [
            'course_code' => 'etika-profesional',
            'activity_type' => 'Magang',
            'activity_semester' => 5,
            'status' => EnrichmentRecord::STATUS_DIKONVERSI,
            'conversion_semester' => 5,
            'grade' => 'C',
        ])->assertRedirect(route('mhs.pengayaan'));

        $this->assertDatabaseHas('enrichment_records', [
            'student_ref' => $student->student_ref,
            'course_code' => 'etika-profesional',
            'grade' => 'C',
        ]);
    }

    public function test_m1_throttle_email_berlaku_pada_refresh_otomatis(): void
    {
        Mail::fake();
        [$student] = $this->seedRuleAndUsers();
        StudentSetting::create([
            'student_ref' => $student->student_ref,
            'remind_in_app' => true,
            'remind_email' => true,
        ]);

        // Sem 5 awal: risiko tinggi -> kirim email 1
        AcademicRecord::create([
            'student_ref' => $student->student_ref,
            'semester' => 5,
            'course_code' => 'IF501',
            'course_name' => 'Big Data',
            'credits' => 3,
            'grade_point' => 0.0,
            'grade_input' => 'E',
            'attendance' => 55,
            'is_official_khs' => true,
        ]);
        app(RiskService::class)->refresh($student->student_ref, false);

        // Osilasi ke sedang lalu kembali ke tinggi dalam window throttle
        AcademicRecord::where('student_ref', $student->student_ref)->update(['grade_point' => 2.0, 'attendance' => 80]);
        app(RiskService::class)->refresh($student->student_ref, false);

        AcademicRecord::where('student_ref', $student->student_ref)->update(['grade_point' => 0.0, 'attendance' => 55]);
        app(RiskService::class)->refresh($student->student_ref, false);

        // Email untuk level "tinggi" hanya terkirim 1 kali dalam jendela throttle
        Mail::assertSent(ReminderMail::class, fn ($m) => $m->result?->level === 'tinggi');
        $tinggiSent = Mail::sent(ReminderMail::class, fn ($m) => $m->result?->level === 'tinggi')->count();
        $this->assertSame(1, $tinggiSent);
    }

    public function test_m3_onboarding_dismiss_menyimpan_timestamp(): void
    {
        [$student] = $this->seedRuleAndUsers();
        $this->assertNull($student->onboarding_seen_at);

        $this->actingAs($student)->post(route('onboarding.dismiss'))->assertRedirect();
        $this->assertNotNull($student->fresh()->onboarding_seen_at);
    }

    public function test_s1_s2_s3_alur_tiket_aman_tanpa_password_plaintext_dan_wajib_2_faktor(): void
    {
        Mail::fake();
        [, $admin] = $this->seedRuleAndUsers();

        // S2: Pengajuan menghasilkan kode 10 karakter acak (total 23 karakter)
        $this->post(route('tiket.submit'), [
            'request_type' => AccountTicket::TYPE_STUDENT,
            'name' => 'Pemohon Baru',
            'username' => '2303045777',
            'program_studi' => AccountTicket::PROGRAMS[0],
            'email' => 'pemohon@example.com',
        ])->assertRedirect(route('tiket.form'));

        $ticket = AccountTicket::where('username', '2303045777')->firstOrFail();
        $this->assertMatchesRegularExpression('/^TKT-\d{8}-[A-Z0-9]{10}$/', $ticket->ticket_code);

        // S2: Cek status hanya dengan kode tanpa faktor kedua tidak membocorkan nama
        $this->get(route('tiket.form', ['kode' => $ticket->ticket_code]))
            ->assertOk()
            ->assertDontSee('Pemohon Baru');

        // S2: Cek status dengan kode + NIM cocok menampilkan status
        $this->get(route('tiket.form', ['kode' => $ticket->ticket_code, 'verifikasi' => '2303045777']))
            ->assertOk()
            ->assertSee('Pemohon Baru');

        // S1: Persetujuan admin mengirim tautan set-password sekali pakai (bukan password plaintext)
        $this->actingAs($admin)->post(route('admin.tiket.setujui', $ticket))
            ->assertRedirect(route('admin.tiket'));

        $capturedUrl = null;
        Mail::assertSent(AccountTicketMail::class, function (AccountTicketMail $m) use (&$capturedUrl) {
            $capturedUrl = $m->setPasswordUrl;

            return $m->hasTo('pemohon@example.com') && is_string($m->setPasswordUrl) && str_contains($m->setPasswordUrl, '/atur-kata-sandi/');
        });

        $this->assertNotNull($capturedUrl);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'pemohon@example.com']);

        // S1: Pengguna mengatur kata sandi dengan token sekali pakai
        auth()->logout();
        $token = basename((string) parse_url($capturedUrl, PHP_URL_PATH));
        $this->post(route('sandi.atur.store'), [
            'token' => $token,
            'email' => 'pemohon@example.com',
            'password' => 'SandiBaru123!',
            'password_confirmation' => 'SandiBaru123!',
        ])->assertRedirect(route('login'));

        $createdUser = User::where('username', '2303045777')->firstOrFail();
        $this->assertTrue(Hash::check('SandiBaru123!', $createdUser->password));
        $this->assertNull(DB::table('password_reset_tokens')->where('email', 'pemohon@example.com')->first());

        // S3: Tiket reset untuk username yang tidak dikenal HARUS ditolak, bukan membuat akun baru
        $unknownReset = AccountTicket::create([
            'ticket_code' => 'TKT-20261125-RESETTEST1',
            'request_type' => AccountTicket::TYPE_RESET,
            'name' => 'Akun Palsu',
            'username' => '9999999999',
            'email' => 'palsu@example.com',
            'status' => AccountTicket::STATUS_PENDING,
        ]);

        $this->actingAs($admin)->post(route('admin.tiket.setujui', $unknownReset))
            ->assertSessionHasErrors('ticket');

        $this->assertDatabaseMissing('users', ['username' => '9999999999']);
        $this->assertSame(AccountTicket::STATUS_PENDING, $unknownReset->fresh()->status);
    }

    public function test_c4_dan_c5_quick_update_dan_store_manual_mempertahankan_khs_dan_grade_point(): void
    {
        [$student] = $this->seedRuleAndUsers();

        $rec = AcademicRecord::create([
            'student_ref' => $student->student_ref,
            'semester' => 5,
            'course_code' => 'IF501',
            'course_name' => 'Big Data',
            'credits' => 3,
            'grade_point' => 3.0,
            'grade_input' => 'B',
            'attendance' => 90,
            'is_official_khs' => true,
        ]);

        // C4: quickUpdate tidak menimpa is_official_khs
        $this->actingAs($student)->post(route('mhs.input-cepat'), [
            'semester' => 5,
            'courses' => [[
                'id' => $rec->id,
                'attendance' => 92,
                'uts_score' => 75,
                'assignment_score' => 80,
                'uas_score' => null,
            ]],
        ])->assertRedirect();

        $this->assertTrue((bool) $rec->fresh()->is_official_khs);

        // C5: storeManualCourses mempertahankan grade_point & grade_input saat re-add MK yang sudah ada
        $this->actingAs($student)->post(route('mhs.tambah-mk'), [
            'semester' => 5,
            'courses' => [[
                'course_code' => 'IF501',
                'course_name' => 'Big Data',
                'credits' => 3,
                'attendance' => 95,
                'uts_score' => 78,
                'assignment_score' => 82,
                'uas_score' => null,
            ]],
        ])->assertRedirect();

        $updated = $rec->fresh();
        $this->assertSame(3.0, (float) $updated->grade_point);
        $this->assertSame('B', $updated->grade_input);
        $this->assertTrue((bool) $updated->is_official_khs);
    }
}
