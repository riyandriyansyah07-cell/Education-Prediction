<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicRecord;
use App\Models\Consent;
use App\Models\Reminder;
use App\Models\RiskResult;
use App\Models\RiskSnapshot;
use App\Services\AccessLogger;
use App\Services\RiskService;
use App\Support\RiskModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SummaryController extends Controller
{
    public function show(Request $request, RiskService $risk): View
    {
        $user = $request->user();
        $ref = $user->student_ref;

        $hasData = AcademicRecord::where('student_ref', $ref)->exists();
        $results = RiskResult::where('student_ref', $ref)->orderBy('semester')->get();
        if ($hasData && $results->isEmpty()) {
            $risk->refresh($ref);
            $results = RiskResult::where('student_ref', $ref)->orderBy('semester')->get();
        }

        $requestedSemester = $request->integer('semester');
        $selected = $requestedSemester > 0
            ? ($results->firstWhere('semester', $requestedSemester) ?? $results->last())
            : $results->last();

        if ($selected) {
            AccessLogger::record($user, $ref, 'lihat_hasil');
        }

        $currentCourses = $selected
            ? AcademicRecord::where('student_ref', $ref)->where('semester', $selected->semester)->orderBy('course_code')->get()
            : collect();

        $snapshots = $selected
            ? RiskSnapshot::where('student_ref', $ref)->where('semester', $selected->semester)->orderByDesc('checked_at')->limit(6)->get()
            : collect();

        $reminders = Reminder::where('student_ref', $ref)->latest('id')->limit(4)->get();
        $unreadIds = $reminders->whereNull('read_at')->pluck('id')->all();
        if ($unreadIds) {
            Reminder::whereIn('id', $unreadIds)->update(['read_at' => now()]);
        }

        $consent = $user->dpa_id
            ? Consent::where('student_ref', $ref)->where('dpa_id', $user->dpa_id)->first()
            : null;

        return view('student.summary', [
            'user' => $user,
            'hasData' => $hasData,
            'results' => $results,
            'latest' => $selected,
            'latestSemester' => $results->last()?->semester,
            'currentCourses' => $currentCourses,
            'snapshots' => $snapshots,
            'version' => $selected?->modelVersion,
            'reminders' => $reminders,
            'unreadIds' => $unreadIds,
            'dpa' => $user->dpa,
            'consent' => $consent,
        ]);
    }

    public function recalculate(Request $request, RiskService $risk): RedirectResponse
    {
        $result = $risk->refresh($request->user()->student_ref, true);
        $semester = $request->integer('semester');

        $redirect = $semester > 0
            ? redirect()->route('mhs.ringkasan', ['semester' => $semester])
            : redirect()->route('mhs.ringkasan');

        return $redirect->with('status', $result
            ? 'Risiko dihitung ulang dengan data, proyeksi mata kuliah, dan AI terbaru.'
            : 'Belum ada data akademik. Tambahkan mata kuliah langsung atau impor berkas dulu.');
    }

    /**
     * Cek Berkala Langsung: Mahasiswa dapat langsung menambah mata kuliah semester berjalan
     * (atau memperbarui bila kode MK & semester sama) tanpa wajib mengimpor file CSV/XLSX dulu.
     * C5: Mempertahankan grade_point, grade_input, dan is_official_khs lama bila mata kuliah sudah ada.
     */
    public function storeManualCourses(Request $request, RiskService $risk): RedirectResponse
    {
        $user = $request->user();
        $ref = $user->student_ref;

        $data = $request->validate([
            'semester' => ['required', 'integer', 'min:1', 'max:14'],
            'courses' => ['required', 'array', 'min:1', 'max:15'],
            'courses.*.course_code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9 .\-_]{2,20}$/'],
            'courses.*.course_name' => ['required', 'string', 'max:120'],
            'courses.*.credits' => ['required', 'integer', 'min:1', 'max:6'],
            'courses.*.attendance' => ['required', 'numeric', 'min:0', 'max:100'],
            'courses.*.activity_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'courses.*.uts_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'courses.*.assignment_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'courses.*.uas_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'courses.*.assignment_source' => ['nullable', Rule::in(['mandiri', 'olu', 'gdrive'])],
        ], [
            'courses.*.course_code.regex' => 'Kode MK harus 2–20 karakter huruf/angka.',
        ]);

        $semester = (int) $data['semester'];
        $today = now()->toDateString();
        $savedCount = 0;

        foreach ($data['courses'] as $item) {
            $code = strtoupper(trim($item['course_code']));
            $aktif = isset($item['activity_score']) && $item['activity_score'] !== '' ? round((float) $item['activity_score'], 2) : null;
            $uts = isset($item['uts_score']) && $item['uts_score'] !== '' ? round((float) $item['uts_score'], 2) : null;
            $tugas = isset($item['assignment_score']) && $item['assignment_score'] !== '' ? round((float) $item['assignment_score'], 2) : null;
            $uas = isset($item['uas_score']) && $item['uas_score'] !== '' ? round((float) $item['uas_score'], 2) : null;
            $source = ($item['assignment_source'] ?? '') !== '' ? $item['assignment_source'] : ($tugas !== null ? 'mandiri' : null);
            $att = round((float) $item['attendance'], 2);

            $existing = AcademicRecord::where('student_ref', $ref)
                ->where('semester', $semester)
                ->where('course_code', $code)
                ->first();

            $preservedGradePoint = $existing?->grade_point !== null ? (float) $existing->grade_point : null;
            $preservedGradeInput = $existing?->grade_input;
            $preservedOfficialKhs = (bool) ($existing?->is_official_khs ?? false);

            $proj = RiskModel::projectCourse([
                'credits' => (int) $item['credits'],
                'attendance' => $att,
                'activity_score' => $aktif,
                'grade_point' => $preservedGradePoint,
                'grade_input' => $preservedGradeInput,
                'uts_score' => $uts,
                'assignment_score' => $tugas,
                'uas_score' => $uas,
                'assignment_source' => $source,
                'is_official_khs' => $preservedOfficialKhs,
            ]);

            AcademicRecord::updateOrCreate(
                [
                    'student_ref' => $ref,
                    'semester' => $semester,
                    'course_code' => $code,
                ],
                [
                    'course_name' => trim($item['course_name']),
                    'credits' => (int) $item['credits'],
                    'grade_point' => $preservedGradePoint,
                    'grade_input' => $preservedGradeInput,
                    'attendance' => $att,
                    'activity_score' => $aktif,
                    'uts_score' => $uts,
                    'assignment_score' => $tugas,
                    'uas_score' => $uas,
                    'assignment_source' => $source,
                    'assignment_recorded_at' => $tugas !== null ? ($existing?->assignment_recorded_at ?? $today) : null,
                    'projected_score' => $proj['projected_score'],
                    'min_uas_for_c' => $proj['min_uas_for_c'],
                    'min_uas_for_b' => $proj['min_uas_for_b'],
                    'course_risk_level' => $proj['course_risk_level'],
                    'data_trust_level' => $proj['data_trust_level'],
                    'is_official_khs' => $preservedOfficialKhs,
                ]
            );
            $savedCount++;
        }

        if ($savedCount > 0) {
            AccessLogger::record($user, $ref, 'input_cepat');
            $risk->refresh($ref, true);
        }

        return redirect()->route('mhs.ringkasan', ['semester' => $semester])->with(
            'status',
            "{$savedCount} mata kuliah semester {$semester} berhasil disimpan dan risiko langsung dihitung."
        );
    }

    /**
     * Input cepat nilai UTS, Tugas, dan UAS per mata kuliah pada semester yang sedang dibuka
     * untuk memperbarui proyeksi nilai akhir (N) dan target UAS minimal (U_min).
     * C4: Tidak menghitung ulang / menimpa flag is_official_khs yang sudah ditetapkan sebelumnya.
     */
    public function quickUpdate(Request $request, RiskService $risk): RedirectResponse
    {
        $user = $request->user();
        $ref = $user->student_ref;

        $data = $request->validate([
            'semester' => ['nullable', 'integer', 'min:1', 'max:14'],
            'courses' => ['required', 'array', 'min:1'],
            'courses.*.id' => ['required', 'integer'],
            'courses.*.attendance' => ['required', 'numeric', 'min:0', 'max:100'],
            'courses.*.activity_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'courses.*.uts_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'courses.*.assignment_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'courses.*.uas_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'courses.*.assignment_source' => ['nullable', Rule::in(['mandiri', 'olu', 'gdrive'])],
        ]);

        $today = now()->toDateString();
        $updatedCount = 0;
        $targetSemester = isset($data['semester']) ? (int) $data['semester'] : null;

        foreach ($data['courses'] as $item) {
            $record = AcademicRecord::where('id', (int) $item['id'])
                ->where('student_ref', $ref)
                ->first();

            if (! $record) {
                continue;
            }

            $targetSemester ??= (int) $record->semester;

            $aktif = isset($item['activity_score']) && $item['activity_score'] !== '' ? (float) $item['activity_score'] : null;
            $uts = isset($item['uts_score']) && $item['uts_score'] !== '' ? (float) $item['uts_score'] : null;
            $tugas = isset($item['assignment_score']) && $item['assignment_score'] !== '' ? (float) $item['assignment_score'] : null;
            $uas = isset($item['uas_score']) && $item['uas_score'] !== '' ? (float) $item['uas_score'] : null;
            $source = ($item['assignment_source'] ?? '') !== '' ? $item['assignment_source'] : ($tugas !== null ? 'mandiri' : null);

            $record->update([
                'attendance' => round((float) $item['attendance'], 2),
                'activity_score' => $aktif !== null ? round($aktif, 2) : null,
                'uts_score' => $uts !== null ? round($uts, 2) : null,
                'assignment_score' => $tugas !== null ? round($tugas, 2) : null,
                'uas_score' => $uas !== null ? round($uas, 2) : null,
                'assignment_source' => $source,
                'assignment_recorded_at' => $tugas !== null ? ($record->assignment_recorded_at ?? $today) : null,
            ]);
            $updatedCount++;
        }

        if ($updatedCount > 0) {
            AccessLogger::record($user, $ref, 'input_cepat');
            $risk->refresh($ref, false);
        }

        $redirect = $targetSemester
            ? redirect()->route('mhs.ringkasan', ['semester' => $targetSemester])
            : redirect()->route('mhs.ringkasan');

        return $redirect->with(
            'status',
            "Proyeksi {$updatedCount} mata kuliah berhasil diperbarui dan jejak cek berkala telah disimpan."
        );
    }
}