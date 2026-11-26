<?php

namespace Tests\Feature;

use App\Models\SkpiDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SkpiDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_unggah_bukti_skpi_dan_dpa_serta_admin_dapat_verifikasi(): void
    {
        Storage::fake('local');

        $admin = User::create([
            'username' => 'admin-skpi',
            'name' => 'Sekretariat FTII',
            'password' => 'rahasia',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $dpa = User::create([
            'username' => 'dpa-skpi',
            'name' => 'DPA Pembimbing',
            'password' => 'rahasia',
            'role' => User::ROLE_DPA,
            'is_active' => true,
        ]);

        $otherDpa = User::create([
            'username' => 'dpa-lain-skpi',
            'name' => 'DPA Lain',
            'password' => 'rahasia',
            'role' => User::ROLE_DPA,
            'is_active' => true,
        ]);

        $student = User::create([
            'username' => '2303045099',
            'name' => 'Mahasiswa SKPI',
            'password' => 'rahasia',
            'role' => User::ROLE_STUDENT,
            'dpa_id' => $dpa->id,
            'student_ref' => hash('sha256', 'skpi-doc-test-ref'),
            'is_active' => true,
        ]);

        $pdf = UploadedFile::fake()->create('sertifikat-it.pdf', 120, 'application/pdf');

        $this->actingAs($student)->post(route('mhs.pengayaan.skpi'), [
            'skpi_it_status' => 'selesai',
            'skpi_it_provider' => 'BNSP',
            'skpi_baps_status' => 'belum',
            'skpi_pkm_stage' => 'belum',
            'skpi_toefl_status' => 'belum',
            'proof_it' => $pdf,
        ])->assertRedirect(route('mhs.pengayaan'));

        $doc = SkpiDocument::where('student_ref', $student->student_ref)
            ->where('type', SkpiDocument::TYPE_IT)
            ->firstOrFail();

        $this->assertSame(SkpiDocument::STATUS_MENUNGGU, $doc->verification_status);
        Storage::disk('local')->assertExists($doc->file_path);

        // Pemilik, DPA pembimbing, dan Admin boleh melihat berkas; DPA lain diblokir
        $this->actingAs($student)->get(route('skpi.dokumen.show', $doc))->assertOk();
        $this->actingAs($dpa)->get(route('skpi.dokumen.show', $doc))->assertOk();
        $this->actingAs($admin)->get(route('skpi.dokumen.show', $doc))->assertOk();
        $this->actingAs($otherDpa)->get(route('skpi.dokumen.show', $doc))->assertForbidden();

        // DPA pembimbing dapat memverifikasi
        $this->actingAs($dpa)->post(route('skpi.dokumen.verifikasi', $doc), [
            'decision' => SkpiDocument::STATUS_TERVERIFIKASI,
            'verification_note' => 'Sertifikat BNSP valid',
        ])->assertRedirect();

        $doc->refresh();
        $this->assertSame(SkpiDocument::STATUS_TERVERIFIKASI, $doc->verification_status);
        $this->assertSame($dpa->id, $doc->verified_by);

        // DPA lain tidak boleh memverifikasi
        $this->actingAs($otherDpa)->post(route('skpi.dokumen.verifikasi', $doc), [
            'decision' => SkpiDocument::STATUS_DITOLAK,
            'verification_note' => 'Tidak sah',
        ])->assertForbidden();

        // Admin juga dapat meninjau & meminta perbaikan
        $this->actingAs($admin)->get(route('admin.skpi'))->assertOk()->assertSee('Mahasiswa SKPI');
        $this->actingAs($admin)->post(route('skpi.dokumen.verifikasi', $doc), [
            'decision' => SkpiDocument::STATUS_DITOLAK,
            'verification_note' => 'Mohon unggah scan lebih jelas',
        ])->assertRedirect();

        $doc->refresh();
        $this->assertSame(SkpiDocument::STATUS_DITOLAK, $doc->verification_status);
        $this->assertSame($admin->id, $doc->verified_by);
    }
}
