<?php

namespace App\Http\Controllers;

use App\Models\SkpiDocument;
use App\Models\User;
use App\Services\AccessLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SkpiDocumentController extends Controller
{
    /** Tampilkan berkas bukti SKPI secara privat (hanya pemilik, DPA bimbingannya, atau Admin). */
    public function show(Request $request, SkpiDocument $document): BinaryFileResponse
    {
        $user = $request->user();
        $this->authorizeView($user, $document);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->file_path), 404, 'Berkas bukti SKPI tidak ditemukan.');

        if ($user->role === User::ROLE_DPA || $user->isAdmin()) {
            AccessLogger::record($user, $document->student_ref, 'lihat_bukti_skpi');
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) $document->original_name) ?: 'bukti-skpi';

        return response()->file($disk->path($document->file_path), [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$safeName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Verifikasi atau tolak bukti SKPI (dapat dilakukan oleh DPA pembimbing atau Admin Sekretariat). */
    public function verify(Request $request, SkpiDocument $document): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeVerify($user, $document);

        $data = $request->validate([
            'decision' => ['required', Rule::in([SkpiDocument::STATUS_TERVERIFIKASI, SkpiDocument::STATUS_DITOLAK])],
            'verification_note' => [
                Rule::requiredIf($request->input('decision') === SkpiDocument::STATUS_DITOLAK),
                'nullable',
                'string',
                'max:255',
            ],
        ], [
            'verification_note.required' => 'Tuliskan catatan singkat alasan perbaikan saat menolak bukti SKPI.',
        ]);

        $isApproved = $data['decision'] === SkpiDocument::STATUS_TERVERIFIKASI;

        $document->update([
            'verification_status' => $data['decision'],
            'verified_by' => $user->id,
            'verified_at' => now(),
            'verification_note' => ($data['verification_note'] ?? null) ? trim($data['verification_note']) : null,
        ]);

        AccessLogger::record($user, $document->student_ref, 'verifikasi_skpi');

        $msg = $isApproved
            ? "Bukti {$document->typeLabel()} berhasil diverifikasi."
            : "Bukti {$document->typeLabel()} ditandai perlu diperbaiki.";

        return back()->with('status', $msg);
    }

    /** Hapus berkas bukti SKPI oleh mahasiswa pemiliknya. */
    public function destroy(Request $request, SkpiDocument $document): RedirectResponse
    {
        abort_unless($document->student_ref === $request->user()->student_ref, 403);

        $label = $document->typeLabel();
        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }
        $document->delete();

        return redirect()->route('mhs.pengayaan')
            ->with('status', "Berkas bukti {$label} dihapus.");
    }

    /** Halaman antrean Verifikasi Bukti SKPI untuk Admin Sekretariat. */
    public function adminIndex(Request $request): View
    {
        $statusFilter = (string) $request->query('status', 'semua');
        if (! in_array($statusFilter, ['semua', SkpiDocument::STATUS_MENUNGGU, SkpiDocument::STATUS_TERVERIFIKASI, SkpiDocument::STATUS_DITOLAK], true)) {
            $statusFilter = 'semua';
        }

        $docs = collect();
        $counts = ['semua' => 0, 'menunggu' => 0, 'terverifikasi' => 0, 'ditolak' => 0];
        $studentsByRef = collect();

        if (Schema::hasTable('skpi_documents')) {
            $all = SkpiDocument::with('verifier')
                ->orderByRaw("CASE verification_status WHEN 'menunggu' THEN 0 WHEN 'ditolak' THEN 1 ELSE 2 END")
                ->orderByDesc('updated_at')
                ->get();

            $counts['semua'] = $all->count();
            $counts['menunggu'] = $all->where('verification_status', SkpiDocument::STATUS_MENUNGGU)->count();
            $counts['terverifikasi'] = $all->where('verification_status', SkpiDocument::STATUS_TERVERIFIKASI)->count();
            $counts['ditolak'] = $all->where('verification_status', SkpiDocument::STATUS_DITOLAK)->count();

            $docs = $statusFilter === 'semua'
                ? $all
                : $all->where('verification_status', $statusFilter)->values();

            $refs = $docs->pluck('student_ref')->unique()->all();
            if ($refs !== []) {
                $studentsByRef = User::with('dpa')
                    ->where('role', User::ROLE_STUDENT)
                    ->whereIn('student_ref', $refs)
                    ->get()
                    ->keyBy('student_ref');
            }
        }

        return view('admin.skpi', [
            'docs' => $docs,
            'counts' => $counts,
            'statusFilter' => $statusFilter,
            'studentsByRef' => $studentsByRef,
        ]);
    }

    private function authorizeView(User $user, SkpiDocument $document): void
    {
        if ($user->isAdmin()) {
            return;
        }
        if ($user->role === User::ROLE_STUDENT && $user->student_ref === $document->student_ref) {
            return;
        }
        if ($user->role === User::ROLE_DPA) {
            $isAdvisee = User::where('role', User::ROLE_STUDENT)
                ->where('student_ref', $document->student_ref)
                ->where('dpa_id', $user->id)
                ->exists();
            if ($isAdvisee) {
                return;
            }
        }
        abort(403);
    }

    private function authorizeVerify(User $user, SkpiDocument $document): void
    {
        if ($user->isAdmin()) {
            return;
        }
        if ($user->role === User::ROLE_DPA) {
            $isAdvisee = User::where('role', User::ROLE_STUDENT)
                ->where('student_ref', $document->student_ref)
                ->where('dpa_id', $user->id)
                ->exists();
            if ($isAdvisee) {
                return;
            }
        }
        abort(403);
    }
}
