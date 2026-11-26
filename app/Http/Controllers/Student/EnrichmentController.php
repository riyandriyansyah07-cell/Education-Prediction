<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicRecord;
use App\Models\EnrichmentRecord;
use App\Models\SkpiDocument;
use App\Models\StudentSetting;
use App\Services\RiskService;
use App\Support\EnrichmentCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EnrichmentController extends Controller
{
    public function index(Request $request): View
    {
        $ref = $request->user()->student_ref;
        $records = EnrichmentRecord::where('student_ref', $ref)
            ->orderByRaw("CASE status WHEN 'dikonversi' THEN 0 WHEN 'siap_rekognisi' THEN 1 ELSE 2 END")
            ->orderBy('activity_semester')
            ->orderBy('course_name')
            ->get();

        $detectedSemester = (int) (AcademicRecord::where('student_ref', $ref)->max('semester') ?? 0);
        $maxActivitySem = (int) ($records->max('activity_semester') ?? 0);
        $currentSemester = (int) $request->query('semester', max(1, $detectedSemester, $maxActivitySem));
        $currentSemester = max(1, min(14, $currentSemester));

        $setting = StudentSetting::firstOrCreate(['student_ref' => $ref]);
        $progress = EnrichmentCatalog::evaluateProgress($records, $currentSemester, $setting);
        $catalog = EnrichmentCatalog::all();

        $skpiDocs = Schema::hasTable('skpi_documents')
            ? SkpiDocument::with('verifier')->where('student_ref', $ref)->get()->keyBy('type')
            : collect();

        return view('student.enrichment', [
            'records' => $records,
            'setting' => $setting,
            'progress' => $progress,
            'catalog' => $catalog,
            'takenCodes' => $records->pluck('course_code')->all(),
            'skpiDocs' => $skpiDocs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $codes = array_column(EnrichmentCatalog::all(), 'code');

        $data = $request->validate([
            'course_code' => ['required', 'string', Rule::in($codes)],
            'activity_type' => ['required', 'string', 'max:160'],
            'activity_semester' => ['required', 'integer', 'min:1', 'max:14'],
            'status' => ['required', Rule::in(EnrichmentRecord::STATUSES)],
            'conversion_semester' => [
                Rule::requiredIf($request->input('status') === EnrichmentRecord::STATUS_DIKONVERSI),
                'nullable',
                'integer',
                'min:'.EnrichmentCatalog::MIN_CONVERSION_SEMESTER,
                'max:14',
            ],
            'grade' => [
                Rule::requiredIf($request->input('status') === EnrichmentRecord::STATUS_DIKONVERSI),
                'nullable',
                Rule::in(['A', 'B', 'C', 'D', 'E']),
            ],
            'evidence_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'conversion_semester.min' => 'Konversi SKS MK Pengayaan ke KRS hanya dapat dilakukan mulai semester 5 ke atas.',
            'conversion_semester.required' => 'Isi semester konversi KRS (minimal semester 5) untuk status Sudah dikonversi.',
            'grade.required' => 'Pilih nilai akhir (A–E) untuk status Sudah dikonversi.',
        ]);

        $course = EnrichmentCatalog::find($data['course_code']);
        $ref = $request->user()->student_ref;
        $isConverted = $data['status'] === EnrichmentRecord::STATUS_DIKONVERSI;
        $isFailedGrade = $isConverted && strtoupper((string) $data['grade']) === 'E';

        EnrichmentRecord::updateOrCreate(
            [
                'student_ref' => $ref,
                'course_code' => $course['code'],
            ],
            [
                'course_name' => $course['name'],
                'credits' => $course['credits'],
                'competency' => $course['competency'],
                'lecturer' => $course['lecturer'],
                'activity_type' => trim($data['activity_type']),
                'activity_semester' => (int) $data['activity_semester'],
                'conversion_semester' => ($isConverted && ! $isFailedGrade) ? (int) $data['conversion_semester'] : null,
                'status' => $isFailedGrade ? EnrichmentRecord::STATUS_DICICIL : $data['status'],
                'grade' => $isConverted ? $data['grade'] : null,
                'evidence_notes' => ($data['evidence_notes'] ?? null) ? trim($data['evidence_notes']) : null,
            ]
        );

        $flash = $isFailedGrade
            ? "Catatan MK Pengayaan \"{$course['name']}\" disimpan dengan nilai E (belum lulus konversi, status otomatis diatur ke Sedang dicicil)."
            : "Catatan MK Pengayaan \"{$course['name']}\" ({$course['credits']} SKS) berhasil disimpan.";

        return redirect()->route('mhs.pengayaan')->with('status', $flash);
    }

    public function updateStatus(Request $request, EnrichmentRecord $record): RedirectResponse
    {
        abort_unless($record->student_ref === $request->user()->student_ref, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(EnrichmentRecord::STATUSES)],
            'conversion_semester' => [
                Rule::requiredIf($request->input('status') === EnrichmentRecord::STATUS_DIKONVERSI),
                'nullable',
                'integer',
                'min:'.EnrichmentCatalog::MIN_CONVERSION_SEMESTER,
                'max:14',
            ],
            'grade' => [
                Rule::requiredIf($request->input('status') === EnrichmentRecord::STATUS_DIKONVERSI),
                'nullable',
                Rule::in(['A', 'B', 'C', 'D', 'E']),
            ],
        ], [
            'conversion_semester.min' => 'Konversi ke KRS minimal dilakukan pada semester 5.',
            'grade.required' => 'Pilih nilai akhir (A–E) untuk status Sudah dikonversi.',
        ]);

        $isConverted = $data['status'] === EnrichmentRecord::STATUS_DIKONVERSI;
        $isFailedGrade = $isConverted && strtoupper((string) $data['grade']) === 'E';

        $record->update([
            'status' => $isFailedGrade ? EnrichmentRecord::STATUS_DICICIL : $data['status'],
            'conversion_semester' => ($isConverted && ! $isFailedGrade) ? (int) $data['conversion_semester'] : null,
            'grade' => $isConverted ? $data['grade'] : null,
        ]);

        return redirect()->route('mhs.pengayaan')
            ->with('status', "Status \"{$record->course_name}\" diperbarui menjadi {$record->statusLabel()}.");
    }

    public function destroy(Request $request, EnrichmentRecord $record): RedirectResponse
    {
        abort_unless($record->student_ref === $request->user()->student_ref, 403);

        $name = $record->course_name;
        $record->delete();

        return redirect()->route('mhs.pengayaan')
            ->with('status', "Catatan MK Pengayaan \"{$name}\" dihapus.");
    }

    public function updateSkpi(Request $request, RiskService $risk): RedirectResponse
    {
        $data = $request->validate([
            'skpi_it_status' => ['required', Rule::in([StudentSetting::SKPI_BELUM, StudentSetting::SKPI_PROSES, StudentSetting::SKPI_SELESAI])],
            'skpi_it_provider' => ['nullable', 'string', 'max:80'],
            'skpi_baps_status' => ['required', Rule::in([StudentSetting::SKPI_BELUM, StudentSetting::BAPS_TERDAFTAR, StudentSetting::SKPI_SELESAI])],
            'skpi_pkm_stage' => ['required', Rule::in(StudentSetting::PKM_STAGES)],
            'skpi_toefl_status' => ['required', Rule::in([StudentSetting::SKPI_BELUM, StudentSetting::SKPI_PROSES, StudentSetting::SKPI_SELESAI])],
            'skpi_toefl_score' => ['nullable', 'integer', 'min:200', 'max:677'],
            'proof_it' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'proof_baps' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'proof_pkm' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'proof_toefl' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [
            'proof_it.mimes' => 'Bukti Sertifikat IT harus berformat PDF, JPG, atau PNG.',
            'proof_it.max' => 'Ukuran bukti Sertifikat IT maksimal 2 MB.',
            'proof_baps.mimes' => 'Bukti BAPS harus berformat PDF, JPG, atau PNG.',
            'proof_baps.max' => 'Ukuran bukti BAPS maksimal 2 MB.',
            'proof_pkm.mimes' => 'Bukti Sertifikat PKM harus berformat PDF, JPG, atau PNG.',
            'proof_pkm.max' => 'Ukuran bukti Sertifikat PKM maksimal 2 MB.',
            'proof_toefl.mimes' => 'Bukti TOEFL UHAMKA harus berformat PDF, JPG, atau PNG.',
            'proof_toefl.max' => 'Ukuran bukti TOEFL UHAMKA maksimal 2 MB.',
        ]);

        $ref = $request->user()->student_ref;
        StudentSetting::updateOrCreate(
            ['student_ref' => $ref],
            [
                'skpi_it_status' => $data['skpi_it_status'],
                'skpi_it_provider' => ($data['skpi_it_provider'] ?? null) ? trim($data['skpi_it_provider']) : null,
                'skpi_baps_status' => $data['skpi_baps_status'],
                'skpi_pkm_stage' => $data['skpi_pkm_stage'],
                'skpi_toefl_status' => $data['skpi_toefl_status'],
                'skpi_toefl_score' => $data['skpi_toefl_score'] ?? null,
            ]
        );

        $uploadedCount = 0;
        if (Schema::hasTable('skpi_documents')) {
            $disk = Storage::disk('local');
            foreach (SkpiDocument::TYPES as $type) {
                $field = 'proof_'.$type;
                if (! $request->hasFile($field)) {
                    continue;
                }
                $file = $request->file($field);
                $existing = SkpiDocument::where('student_ref', $ref)->where('type', $type)->first();
                if ($existing && $existing->file_path && $disk->exists($existing->file_path)) {
                    $disk->delete($existing->file_path);
                }
                $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'pdf');
                $storedPath = $file->storeAs("skpi_proofs/{$ref}", "{$type}_".time().".{$ext}", 'local');

                SkpiDocument::updateOrCreate(
                    ['student_ref' => $ref, 'type' => $type],
                    [
                        'file_path' => $storedPath,
                        'original_name' => mb_substr((string) $file->getClientOriginalName(), 0, 180),
                        'mime_type' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
                        'file_size' => (int) ($file->getSize() ?: 0),
                        'verification_status' => SkpiDocument::STATUS_MENUNGGU,
                        'verified_by' => null,
                        'verified_at' => null,
                        'verification_note' => null,
                    ]
                );
                $uploadedCount++;
            }
        }

        $risk->refresh($ref, false);

        $msg = $uploadedCount > 0
            ? "Status SKPI & {$uploadedCount} berkas bukti berhasil disimpan (menunggu verifikasi DPA/Sekretariat)."
            : 'Status 4 syarat wajib SKPI & tahap PKM berhasil diperbarui.';

        return redirect()->route('mhs.pengayaan')->with('status', $msg);
    }
}
