<?php

namespace App\Http\Controllers\Dpa;

use App\Http\Controllers\Controller;
use App\Models\AcademicRecord;
use App\Models\Consent;
use App\Models\DpaNotification;
use App\Models\EnrichmentRecord;
use App\Models\GuidanceNote;
use App\Models\RiskResult;
use App\Models\RiskSnapshot;
use App\Models\SkpiDocument;
use App\Models\StudentSetting;
use App\Models\User;
use App\Services\AccessLogger;
use App\Services\RiskService;
use App\Support\EnrichmentCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class GuidanceController extends Controller
{
    private const ORDER = ['tinggi' => 0, 'sedang' => 1, 'rendah' => 2, 'belum' => 3];

    private const LEVEL_RANK = ['rendah' => 1, 'sedang' => 2, 'tinggi' => 3];

    public function index(Request $request): View
    {
        $dpa = $request->user();
        $students = User::where('dpa_id', $dpa->id)
            ->where('role', User::ROLE_STUDENT)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Akses DPA resmi aktif otomatis untuk seluruh mahasiswa bimbingan yang ditetapkan
        $visible = $students->values();
        $visibleRefs = $visible->pluck('student_ref')->all();

        $allResults = $visibleRefs !== []
            ? RiskResult::whereIn('student_ref', $visibleRefs)
                ->orderByDesc('semester')
                ->get()
                ->groupBy('student_ref')
            : collect();

        $settingsByRef = $visibleRefs !== []
            ? StudentSetting::whereIn('student_ref', $visibleRefs)
                ->get()
                ->keyBy('student_ref')
            : collect();

        $rows = $visible->map(function (User $s) use ($allResults, $settingsByRef) {
            $semResults = $allResults->get($s->student_ref, collect());
            $latest = $semResults->first();
            $previous = $semResults->skip(1)->first();
            $setting = $settingsByRef->get($s->student_ref);

            $escalated = false;
            if ($latest && $previous) {
                $currRank = self::LEVEL_RANK[$latest->level] ?? 0;
                $prevRank = self::LEVEL_RANK[$previous->level] ?? 0;
                $escalated = $currRank > $prevRank;
            }

            $level = $latest?->level ?? 'belum';
            $followUp = $setting?->follow_up_status ?? StudentSetting::STATUS_BELUM;
            $isPriority = in_array($level, ['tinggi', 'sedang'], true)
                && $followUp === StudentSetting::STATUS_BELUM;

            return [
                'student' => $s,
                'result' => $latest,
                'previous_level' => $previous?->level,
                'escalated' => $escalated,
                'level' => $level,
                'setting' => $setting,
                'follow_up_status' => $followUp,
                'is_priority' => $isPriority,
            ];
        });

        $counts = ['semua' => $rows->count()];
        $riskPcts = [];
        $visibleTotal = max(0, $rows->count());
        foreach (['tinggi', 'sedang', 'rendah', 'belum'] as $level) {
            $counts[$level] = $rows->where('level', $level)->count();
            $riskPcts[$level] = $visibleTotal > 0 ? round(($counts[$level] / $visibleTotal) * 100, 1) : 0.0;
        }

        $statusCounts = [
            StudentSetting::STATUS_BELUM => $rows->where('follow_up_status', StudentSetting::STATUS_BELUM)->count(),
            StudentSetting::STATUS_DIJADWALKAN => $rows->where('follow_up_status', StudentSetting::STATUS_DIJADWALKAN)->count(),
            StudentSetting::STATUS_SUDAH => $rows->where('follow_up_status', StudentSetting::STATUS_SUDAH)->count(),
            StudentSetting::STATUS_SELESAI => $rows->where('follow_up_status', StudentSetting::STATUS_SELESAI)->count(),
        ];

        $priorityCount = $rows->where('is_priority', true)->count();
        $escalatedCount = $rows->where('escalated', true)->count();
        $handledCount = $statusCounts[StudentSetting::STATUS_SUDAH] + $statusCounts[StudentSetting::STATUS_SELESAI];
        $handledPct = $visibleTotal > 0 ? round(($handledCount / $visibleTotal) * 100) : 0;

        $semesterStats = $rows
            ->groupBy(fn ($r) => $r['result']?->semester ? (int) $r['result']->semester : 0)
            ->map(function ($group, $semKey) {
                $semNum = (int) $semKey;

                return [
                    'key' => (string) $semNum,
                    'label' => $semNum > 0 ? 'Semester '.$semNum : 'Belum dihitung',
                    'total' => $group->count(),
                    'tinggi' => $group->where('level', 'tinggi')->count(),
                    'sedang' => $group->where('level', 'sedang')->count(),
                    'rendah' => $group->where('level', 'rendah')->count(),
                    'belum' => $group->where('level', 'belum')->count(),
                ];
            })
            ->sortBy(fn ($item) => $item['key'] === '0' ? 999 : (int) $item['key'])
            ->values();

        $factorTally = [];
        foreach ($rows as $r) {
            $factors = $r['result']?->factors;
            if (! is_array($factors) || $factors === []) {
                continue;
            }
            $top = $factors[0]['label'] ?? null;
            if (is_string($top) && trim($top) !== '') {
                $label = trim($top);
                $factorTally[$label] = ($factorTally[$label] ?? 0) + 1;
            }
        }
        arsort($factorTally);
        $topFactors = collect($factorTally)
            ->take(5)
            ->map(fn ($cnt, $label) => ['label' => $label, 'count' => $cnt])
            ->values();

        $highRiskRefs = $rows
            ->where('level', 'tinggi')
            ->map(fn ($r) => $r['student']->student_ref)
            ->values()
            ->all();

        $filter = $request->query('tingkat');
        if (in_array($filter, ['tinggi', 'sedang', 'rendah', 'belum'], true)) {
            $rows = $rows->where('level', $filter);
        } else {
            $filter = null;
        }

        $statusFilter = $request->query('status');
        if (in_array($statusFilter, array_keys($statusCounts), true)) {
            $rows = $rows->where('follow_up_status', $statusFilter);
        } else {
            $statusFilter = null;
        }

        $rows = $rows->sortBy(fn ($r) => self::ORDER[$r['level']].'|'.$r['student']->name)->values();

        AccessLogger::record($dpa, null, 'daftar_bimbingan');

        $notifications = $highRiskRefs !== []
            ? DpaNotification::where('dpa_id', $dpa->id)
                ->whereIn('student_ref', $highRiskRefs)
                ->latest('id')
                ->limit(5)
                ->get()
            : collect();

        $unreadNotifIds = $notifications->whereNull('read_at')->pluck('id')->all();
        if ($unreadNotifIds !== []) {
            DpaNotification::whereIn('id', $unreadNotifIds)->update(['read_at' => now()]);
        }

        return view('dpa.index', [
            'rows' => $rows,
            'counts' => $counts,
            'riskPcts' => $riskPcts,
            'statusCounts' => $statusCounts,
            'priorityCount' => $priorityCount,
            'escalatedCount' => $escalatedCount,
            'handledCount' => $handledCount,
            'handledPct' => $handledPct,
            'semesterStats' => $semesterStats,
            'topFactors' => $topFactors,
            'filter' => $filter,
            'statusFilter' => $statusFilter,
            'total' => $students->count(),
            'pending' => $students->count() - $visible->count(),
            'notifications' => $notifications,
            'unreadNotifIds' => $unreadNotifIds,
        ]);
    }

    /** Detail faktor, rincian nilai mata kuliah (mendukung pilih semester ?semester=N), progres SKS pengayaan, dan catatan DPA. */
    public function show(Request $request, User $student): View
    {
        $dpa = $request->user();
        $this->authorizeStudent($dpa, $student);

        $ref = $student->student_ref;
        $results = RiskResult::with('modelVersion')->where('student_ref', $ref)->orderBy('semester')->get();
        $latestResult = $results->last();

        $requestedSemester = $request->integer('semester');
        $selected = $requestedSemester > 0
            ? ($results->firstWhere('semester', $requestedSemester) ?? $latestResult)
            : $latestResult;

        $setting = StudentSetting::firstOrCreate(['student_ref' => $ref]);

        $courses = $selected
            ? AcademicRecord::where('student_ref', $ref)->where('semester', $selected->semester)->orderBy('course_code')->get()
            : collect();

        $notes = GuidanceNote::where('student_ref', $ref)->orderByDesc('guidance_date')->orderByDesc('id')->get();
        $snapshots = $selected
            ? RiskSnapshot::where('student_ref', $ref)->where('semester', $selected->semester)->orderByDesc('checked_at')->limit(6)->get()
            : collect();

        $enrichmentRecords = Schema::hasTable('enrichment_records')
            ? EnrichmentRecord::where('student_ref', $ref)
                ->orderByRaw("CASE status WHEN 'dikonversi' THEN 0 WHEN 'siap_rekognisi' THEN 1 ELSE 2 END")
                ->orderBy('course_name')
                ->get()
            : collect();
        $enrichment = EnrichmentCatalog::evaluateProgress(
            $enrichmentRecords,
            (int) ($latestResult?->semester ?? max(1, (int) ($enrichmentRecords->max('activity_semester') ?? 1))),
            $setting
        );

        AccessLogger::record($dpa, $ref, 'detail_faktor');

        $skpiDocs = Schema::hasTable('skpi_documents')
            ? SkpiDocument::with('verifier')->where('student_ref', $ref)->get()->keyBy('type')
            : collect();

        return view('dpa.show', [
            'student' => $student,
            'results' => $results,
            'latest' => $selected,
            'latestSemester' => $latestResult?->semester,
            'setting' => $setting,
            'courses' => $courses,
            'notes' => $notes,
            'snapshots' => $snapshots,
            'enrichment' => $enrichment,
            'enrichmentRecords' => $enrichmentRecords,
            'skpiDocs' => $skpiDocs,
        ]);
    }

    /** Simpan pemantauan pendampingan DPA (status tindak lanjut, status tugas akhir, tanggal bimbingan terakhir). */
    public function updateMonitoring(Request $request, User $student, RiskService $risk): RedirectResponse
    {
        $dpa = $request->user();
        $this->authorizeStudent($dpa, $student);

        $data = $request->validate([
            'follow_up_status' => ['required', Rule::in([
                StudentSetting::STATUS_BELUM,
                StudentSetting::STATUS_DIJADWALKAN,
                StudentSetting::STATUS_SUDAH,
                StudentSetting::STATUS_SELESAI,
            ])],
            'thesis_status' => ['nullable', 'string', 'max:24'],
            'last_guidance_at' => ['nullable', 'date'],
        ]);

        $ref = $student->student_ref;

        StudentSetting::updateOrCreate(
            ['student_ref' => $ref],
            [
                'follow_up_status' => $data['follow_up_status'],
                'dpa_activity_level' => null,
                'thesis_status' => ($data['thesis_status'] ?? '') !== '' ? $data['thesis_status'] : null,
                'last_guidance_at' => ($data['last_guidance_at'] ?? '') !== '' ? $data['last_guidance_at'] : null,
                'dpa_confirmed_tasks' => false,
                'dpa_updated_by' => $dpa->id,
            ]
        );

        AccessLogger::record($dpa, $ref, 'input_dpa');
        $risk->refresh($ref, false);

        return redirect()->route('dpa.bimbingan.show', $student)
            ->with('status', 'Status pemantauan pendampingan DPA berhasil diperbarui.');
    }

    /** Simpan catatan pertemuan bimbingan baru (guidance_notes). */
    public function storeNote(Request $request, User $student, RiskService $risk): RedirectResponse
    {
        $dpa = $request->user();
        $this->authorizeStudent($dpa, $student);

        $data = $request->validate([
            'guidance_date' => ['required', 'date'],
            'topic' => ['required', 'string', 'max:160'],
            'follow_up_action' => ['required', 'string', 'max:2000'],
            'follow_up_status' => ['required', Rule::in([
                StudentSetting::STATUS_BELUM,
                StudentSetting::STATUS_DIJADWALKAN,
                StudentSetting::STATUS_SUDAH,
                StudentSetting::STATUS_SELESAI,
            ])],
            'thesis_status' => ['nullable', 'string', 'max:24'],
        ]);

        $ref = $student->student_ref;
        $latestSemester = RiskResult::where('student_ref', $ref)->max('semester');

        GuidanceNote::create([
            'student_ref' => $ref,
            'dpa_id' => $dpa->id,
            'semester' => $latestSemester,
            'guidance_date' => $data['guidance_date'],
            'topic' => $data['topic'],
            'follow_up_action' => $data['follow_up_action'],
            'follow_up_status' => $data['follow_up_status'],
            'activity_assessment' => null,
            'thesis_status' => ($data['thesis_status'] ?? '') !== '' ? $data['thesis_status'] : null,
            'confirmed_course_scores' => false,
        ]);

        $settingUpdate = [
            'follow_up_status' => $data['follow_up_status'],
            'last_guidance_at' => $data['guidance_date'],
            'dpa_updated_by' => $dpa->id,
        ];
        if (! empty($data['thesis_status'])) {
            $settingUpdate['thesis_status'] = $data['thesis_status'];
        }

        StudentSetting::updateOrCreate(['student_ref' => $ref], $settingUpdate);

        AccessLogger::record($dpa, $ref, 'catat_bimbingan');
        $risk->refresh($ref, false);

        return redirect()->route('dpa.bimbingan.show', $student)
            ->with('status', 'Catatan bimbingan disimpan dan riwayat tindak lanjut diperbarui.');
    }

    /** Riwayat seluruh catatan bimbingan yang pernah dicatat DPA (pencarian, status, periode, & paginasi). */
    public function notesHistory(Request $request): View
    {
        $dpa = $request->user();

        $statusFilter = (string) $request->query('status', 'semua');
        if (! in_array($statusFilter, ['semua', StudentSetting::STATUS_BELUM, StudentSetting::STATUS_DIJADWALKAN, StudentSetting::STATUS_SUDAH, StudentSetting::STATUS_SELESAI], true)) {
            $statusFilter = 'semua';
        }

        $periodFilter = (string) $request->query('period', 'semua');
        if (! in_array($periodFilter, ['semua', 'hari', 'minggu', 'bulan'], true)) {
            $periodFilter = 'semua';
        }

        $search = trim((string) $request->query('q', ''));

        $advisees = User::where('dpa_id', $dpa->id)
            ->where('role', User::ROLE_STUDENT)
            ->get()
            ->keyBy('student_ref');
        $adviseeRefs = $advisees->keys()->filter()->all();

        $query = GuidanceNote::query()
            ->where(function ($q) use ($dpa, $adviseeRefs) {
                $q->where('dpa_id', $dpa->id);
                if ($adviseeRefs !== []) {
                    $q->orWhereIn('student_ref', $adviseeRefs);
                }
            });

        if ($periodFilter === 'hari') {
            $query->where('guidance_date', '>=', now()->startOfDay());
        } elseif ($periodFilter === 'minggu') {
            $query->where('guidance_date', '>=', now()->startOfWeek());
        } elseif ($periodFilter === 'bulan') {
            $query->where('guidance_date', '>=', now()->startOfMonth());
        }

        if ($search !== '') {
            $term = mb_strtolower($search);
            $matchedRefs = $advisees
                ->filter(fn (User $s) => str_contains(mb_strtolower((string) $s->name), $term)
                    || str_contains(mb_strtolower((string) $s->username), $term))
                ->keys()
                ->all();

            $query->where(function ($q) use ($term, $matchedRefs) {
                $q->whereRaw('LOWER(topic) LIKE ?', ['%'.$term.'%'])
                    ->orWhereRaw('LOWER(follow_up_action) LIKE ?', ['%'.$term.'%']);
                if ($matchedRefs !== []) {
                    $q->orWhereIn('student_ref', $matchedRefs);
                }
            });
        }

        $statusRows = (clone $query)->pluck('follow_up_status');
        $counts = [
            'semua' => $statusRows->count(),
            StudentSetting::STATUS_DIJADWALKAN => $statusRows->filter(fn ($st) => $st === StudentSetting::STATUS_DIJADWALKAN)->count(),
            StudentSetting::STATUS_SUDAH => $statusRows->filter(fn ($st) => $st === StudentSetting::STATUS_SUDAH)->count(),
            StudentSetting::STATUS_SELESAI => $statusRows->filter(fn ($st) => $st === StudentSetting::STATUS_SELESAI)->count(),
        ];

        if ($statusFilter !== 'semua') {
            $query->where('follow_up_status', $statusFilter);
        }

        $notes = $query
            ->orderByDesc('guidance_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('dpa.notes', [
            'notes' => $notes,
            'advisees' => $advisees,
            'counts' => $counts,
            'statusFilter' => $statusFilter,
            'periodFilter' => $periodFilter,
            'search' => $search,
        ]);
    }

    /** Hapus catatan bimbingan milik DPA (termasuk catatan yatim bila akun mahasiswa sudah dihapus). */
    public function destroyNote(Request $request, GuidanceNote $note): RedirectResponse
    {
        $dpa = $request->user();
        $isOwner = (int) $note->dpa_id === (int) $dpa->id;
        $isAdviseeNote = User::where('dpa_id', $dpa->id)
            ->where('role', User::ROLE_STUDENT)
            ->where('student_ref', $note->student_ref)
            ->exists();

        abort_unless($isOwner || $isAdviseeNote, 403);

        $topic = $note->topic;
        $note->delete();

        return back()->with('status', 'Catatan bimbingan berhasil dihapus.');
    }

    private function authorizeStudent(User $dpa, User $student): void
    {
        abort_unless(
            $student->role === User::ROLE_STUDENT && $student->dpa_id === $dpa->id,
            403
        );


    }
}