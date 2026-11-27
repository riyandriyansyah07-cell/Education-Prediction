<?php

namespace Tests\Feature;

use App\Mail\AdminNewTicketMail;
use App\Models\AccountTicket;
use App\Models\SkpiDocument;
use App\Models\User;
use App\Support\EnrichmentCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecentFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_katalog_pengayaan_mendeteksi_singkatan_khs_dan_menolak_mk_reguler(): void
    {
        $this->assertSame('retorika-presentasi-publik', EnrichmentCatalog::matchCourseName('Keteramp. Ret. & Presen. Publ.')['code'] ?? null);
        $this->assertSame('berpikir-kritis-kreatif', EnrichmentCatalog::matchCourseName('Berpikir Kritis & Kreatif (Ko)')['code'] ?? null);
        $this->assertSame('keberagaman-multibudaya', EnrichmentCatalog::matchCourseName('Keberagaman & Multibudaya')['code'] ?? null);
        $this->assertSame('empati-kecerdasan-sosial', EnrichmentCatalog::matchCourseName('Empati & Kecerd. Sosial')['code'] ?? null);

        // Pastikan MK reguler tidak salah cocok dengan MK Pengayaan
        $this->assertNull(EnrichmentCatalog::matchCourseName('Etika Profesi'));
        $this->assertNull(EnrichmentCatalog::matchCourseName('Metodologi Penelitian'));
    }

    public function test_endpoint_notifikasi_cek_mengembalikan_antrean_tiket_untuk_admin(): void
    {
        $admin = User::create([
            'username' => 'admin-notif',
            'name' => 'Admin Notif',
            'email' => 'admin-notif@uhamka.ac.id',
            'password' => 'rahasia',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        AccountTicket::create([
            'ticket_code' => 'TKT-20261127-TEST01',
            'request_type' => AccountTicket::TYPE_STUDENT,
            'name' => 'Mahasiswa Uji',
            'username' => '2303045991',
            'program_studi' => 'Teknik Informatika',
            'email' => 'mhs.uji@uhamka.ac.id',
            'status' => AccountTicket::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->getJson(route('notifikasi.cek'))
            ->assertOk()
            ->assertJsonPath('role', 'admin')
            ->assertJsonPath('pending_tickets', 1)
            ->assertJsonPath('items.0.title', 'E-Ticket Pengajuan Akun Baru');
    }

    public function test_pengajuan_eticket_baru_mengirim_email_instan_ke_admin(): void
    {
        Mail::fake();

        User::create([
            'username' => 'admin-mail',
            'name' => 'Admin Email',
            'email' => 'sekretariat@uhamka.ac.id',
            'password' => 'rahasia',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->post(route('tiket.submit'), [
            'request_type' => AccountTicket::TYPE_STUDENT,
            'name' => 'Budi Santoso',
            'username' => '2303045992',
            'program_studi' => 'Teknik Informatika',
            'email' => 'budi.santoso@uhamka.ac.id',
            'notes' => 'Mohon aktivasi akun',
        ])->assertRedirect(route('tiket.form'));

        Mail::assertSent(AdminNewTicketMail::class, function (AdminNewTicketMail $mail) {
            return $mail->hasTo('sekretariat@uhamka.ac.id')
                && $mail->ticket->username === '2303045992';
        });
    }

    public function test_mahasiswa_hanya_bisa_hapus_bukti_skpi_yang_belum_terverifikasi(): void
    {
        Storage::fake('local');

        $student = User::create([
            'username' => '2303045993',
            'name' => 'Mahasiswa Hapus SKPI',
            'password' => 'rahasia',
            'role' => User::ROLE_STUDENT,
            'student_ref' => hash('sha256', 'hapus-skpi-test'),
            'is_active' => true,
        ]);

        Storage::disk('local')->put('skpi_proofs/test/it.pdf', 'dummy-pdf');
        $docPending = SkpiDocument::create([
            'student_ref' => $student->student_ref,
            'type' => SkpiDocument::TYPE_IT,
            'file_path' => 'skpi_proofs/test/it.pdf',
            'original_name' => 'it.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'verification_status' => SkpiDocument::STATUS_MENUNGGU,
        ]);

        $this->actingAs($student)
            ->delete(route('mhs.pengayaan.skpi.hapus', $docPending))
            ->assertRedirect(route('mhs.pengayaan'));

        $this->assertDatabaseMissing('skpi_documents', ['id' => $docPending->id]);

        Storage::disk('local')->put('skpi_proofs/test/baps.pdf', 'dummy-pdf');
        $docVerified = SkpiDocument::create([
            'student_ref' => $student->student_ref,
            'type' => SkpiDocument::TYPE_BAPS,
            'file_path' => 'skpi_proofs/test/baps.pdf',
            'original_name' => 'baps.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'verification_status' => SkpiDocument::STATUS_TERVERIFIKASI,
        ]);

        $this->actingAs($student)
            ->delete(route('mhs.pengayaan.skpi.hapus', $docVerified))
            ->assertRedirect(route('mhs.pengayaan'));

        $this->assertDatabaseHas('skpi_documents', ['id' => $docVerified->id]);
    }
}
