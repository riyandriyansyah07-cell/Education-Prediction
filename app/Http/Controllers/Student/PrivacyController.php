<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Mail\ReminderMail;
use App\Models\Consent;
use App\Models\GuidanceNote;
use App\Models\RiskResult;
use App\Models\StudentSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Throwable;

class PrivacyController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $ref = $user->student_ref;

        $guidanceNotes = Schema::hasTable('guidance_notes')
            ? GuidanceNote::with('dpa')
                ->where('student_ref', $ref)
                ->orderByDesc('guidance_date')
                ->orderByDesc('id')
                ->limit(3)
                ->get()
            : collect();

        return view('student.privacy', [
            'user' => $user,
            'dpa' => $user->dpa,
            'consent' => $user->dpa_id
                ? Consent::where('student_ref', $ref)->where('dpa_id', $user->dpa_id)->first()
                : null,
            'settings' => StudentSetting::firstOrCreate(['student_ref' => $ref]),
            'guidanceNotes' => $guidanceNotes,
        ]);
    }

    public function consent(Request $request): RedirectResponse
    {
        return redirect()->route('mhs.privasi')
            ->with('status', 'Akses pendampingan DPA resmi aktif otomatis sesuai kebijakan akademik FTII UHAMKA.');
    }

    public function reminders(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $emailValue = ($data['email'] ?? null) ?: null;
        $email = $request->boolean('remind_email');
        if ($email && $emailValue === null) {
            return back()->withErrors(['email' => 'Isi alamat email kalau kamu mau pengingat lewat email.'])->withInput();
        }

        $previous = StudentSetting::where('student_ref', $user->student_ref)->first();
        $needsConfirmation = $email && (! $previous?->remind_email || $user->email !== $emailValue);

        $user->update(['email' => $emailValue]);
        StudentSetting::updateOrCreate(
            ['student_ref' => $user->student_ref],
            ['remind_in_app' => $request->boolean('remind_in_app'), 'remind_email' => $email]
        );

        $status = 'Pengaturan pengingat disimpan.';
        if ($needsConfirmation) {
            try {
                $latest = RiskResult::where('student_ref', $user->student_ref)->orderByDesc('semester')->first();
                Mail::to($emailValue)->send(new ReminderMail($user, ReminderMail::KIND_CONFIRM, $latest));
                $status .= ' Email konfirmasi dikirim ke '.$emailValue.'. Cek juga folder Spam.';
            } catch (Throwable $e) {
                Log::warning('Email konfirmasi pengingat gagal dikirim: '.$e->getMessage());
                $status .= ' Email konfirmasi belum bisa terkirim. Pastikan alamatnya benar, lalu simpan lagi.';
            }
        }

        return redirect()->route('mhs.privasi')->with('status', $status);
    }
}
