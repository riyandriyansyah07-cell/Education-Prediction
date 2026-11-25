<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicRecord;
use App\Models\EnrichmentRecord;
use App\Support\EnrichmentCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $progress = EnrichmentCatalog::evaluateProgress($records, $currentSemester);
        $catalog = EnrichmentCatalog::all();

        return view('student.enrichment', [
            'records' => $records,
            'progress' => $progress,
            'catalog' => $catalog,
            'takenCodes' => $records->pluck('course_code')->all(),
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
}