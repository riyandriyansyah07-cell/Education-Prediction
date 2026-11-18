<?php

namespace App\Http\Controllers;

use App\Mail\AccountTicketMail;
use App\Models\AccountTicket;
use App\Models\StudentSetting;
use App\Models\User;
use App\Services\Pseudonym;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountTicketController extends Controller
{
    /** Halaman publik pengajuan E-Ticket akun & cek status tiket. */
    public function showForm(Request $request): View
    {
        $code = strtoupper(trim((string) $request->query('kode', '')));
        $checkedTicket = $code !== ''
            ? AccountTicket::where('ticket_code', $code)->first()
            : null;

        return view('tickets.request', [
            'checkedTicket' => $checkedTicket,
            'searchCode' => $code,
        ]);
    }

    /** Simpan pengajuan E-Ticket baru dari halaman publik. */
    public function submit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'request_type' => ['required', Rule::in([
                AccountTicket::TYPE_STUDENT,
                AccountTicket::TYPE_DPA,
                AccountTicket::TYPE_RESET,
            ])],
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'program_studi' => [Rule::requiredIf($request->input('request_type') === AccountTicket::TYPE_STUDENT), 'nullable', Rule::in(AccountTicket::PROGRAMS)],
            'email' => ['nullable', 'email', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'username.regex' => 'NIM atau ID pegawai hanya boleh berisi huruf, angka, titik, dan strip.',
        ]);

        // Cegah duplikasi tiket menunggu untuk username yang sama
        $existingPending = AccountTicket::where('username', $data['username'])
            ->where('status', AccountTicket::STATUS_PENDING)
            ->first();

        if ($existingPending) {
            return redirect()->route('tiket.form', ['kode' => $existingPending->ticket_code])
                ->with('status', "Masih ada tiket aktif ({$existingPending->ticket_code}) untuk NIM/ID ini yang sedang menunggu verifikasi sekretariat.");
        }

        $ticketCode = 'TKT-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));

        $ticket = AccountTicket::create([
            'ticket_code' => $ticketCode,
            'request_type' => $data['request_type'],
            'name' => trim($data['name']),
            'username' => trim($data['username']),
            'email' => ($data['email'] ?? null) ?: null,
            'notes' => ($data['notes'] ?? null) ?: null,
            'status' => AccountTicket::STATUS_PENDING,
        ]);

        return redirect()->route('tiket.form', ['kode' => $ticket->ticket_code])
            ->with('status', "E-Ticket {$ticket->ticket_code} berhasil dibuat! Simpan kode ini untuk mengecek status persetujuan dari Admin Sekretariat.");
    }

    /** Daftar tiket pengajuan akun di panel Admin Sekretariat. */
    public function adminIndex(Request $request): View
    {
        $status = $request->query('status');
        if (! in_array($status, [
            AccountTicket::STATUS_PENDING,
            AccountTicket::STATUS_APPROVED,
            AccountTicket::STATUS_REJECTED,
        ], true)) {
            $status = null;
        }

        $tickets = AccountTicket::with('processor')
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'menunggu' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $pendingCount = AccountTicket::where('status', AccountTicket::STATUS_PENDING)->count();

        return view('admin.tickets', [
            'tickets' => $tickets,
            'status' => $status,
            'pendingCount' => $pendingCount,
            'dpas' => User::where('role', User::ROLE_DPA)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /** Setujui tiket: otomatis buat akun baru atau reset kata sandi pengguna. */
    public function approve(Request $request, AccountTicket $ticket): RedirectResponse
    {
        if ($ticket->status !== AccountTicket::STATUS_PENDING) {
            return back()->with('status', 'Tiket ini sudah diproses sebelumnya.');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:72'],
            'dpa_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_DPA),
            ],
            'admin_note' => ['nullable', 'string', 'max:300'],
        ]);

        DB::transaction(function () use ($ticket, $data, $request) {
            $existingUser = User::where('username', $ticket->username)->first();

            if ($ticket->request_type === AccountTicket::TYPE_RESET) {
                if ($existingUser) {
                    $existingUser->update([
                        'password' => $data['password'],
                        'is_active' => true,
                    ]);
                } else {
                    $role = User::ROLE_STUDENT;
                    $ref = Pseudonym::ref($ticket->username);
                    User::create([
                        'name' => $ticket->name,
                        'username' => $ticket->username,
                        'email' => $ticket->email,
                        'role' => $role,
                        'password' => $data['password'],
                        'student_ref' => $ref,
                        'dpa_id' => ! empty($data['dpa_id']) ? (int) $data['dpa_id'] : null,
                        'is_active' => true,
                    ]);
                    StudentSetting::firstOrCreate(['student_ref' => $ref]);
                }
            } else {
                $role = $ticket->request_type === AccountTicket::TYPE_DPA ? User::ROLE_DPA : User::ROLE_STUDENT;
                if (! $existingUser) {
                    $ref = $role === User::ROLE_STUDENT ? Pseudonym::ref($ticket->username) : null;
                    User::create([
                        'name' => $ticket->name,
                        'username' => $ticket->username,
                        'email' => $ticket->email,
                        'role' => $role,
                        'password' => $data['password'],
                        'student_ref' => $ref,
                        'dpa_id' => $role === User::ROLE_STUDENT && ! empty($data['dpa_id']) ? (int) $data['dpa_id'] : null,
                        'is_active' => true,
                    ]);
                    if ($ref) {
                        StudentSetting::firstOrCreate(['student_ref' => $ref]);
                    }
                } else {
                    $existingUser->update([
                        'password' => $data['password'],
                        'is_active' => true,
                    ]);
                }
            }

            $ticket->update([
                'status' => AccountTicket::STATUS_APPROVED,
                'admin_note' => ($data['admin_note'] ?? null) ?: 'Akun telah aktif dan siap digunakan dengan kata sandi awal yang ditetapkan sekretariat.',
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);
        });

        $mailNote = '';
        if ($ticket->email) {
            try {
                Mail::to($ticket->email)->send(new AccountTicketMail($ticket->fresh(), $data['password']));
                $mailNote = " Notifikasi dikirim ke {$ticket->email}.";
            } catch (\Throwable $e) {
                Log::warning('Email notifikasi persetujuan tiket gagal dikirim: '.$e->getMessage());
                $mailNote = ' Namun email notifikasi gagal terkirim.';
            }
        }

        return redirect()->route('admin.tiket')->with(
            'status',
            "Tiket {$ticket->ticket_code} disetujui. Akun {$ticket->username} kini aktif.{$mailNote}"
        );
    }

    /** Tolak tiket pengajuan akun dengan catatan alasan. */
    public function reject(Request $request, AccountTicket $ticket): RedirectResponse
    {
        if ($ticket->status !== AccountTicket::STATUS_PENDING) {
            return back()->with('status', 'Tiket ini sudah diproses sebelumnya.');
        }

        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:300'],
        ], [
            'admin_note.required' => 'Tuliskan alasan penolakan agar pemohon mengetahuinya.',
        ]);

        $ticket->update([
            'status' => AccountTicket::STATUS_REJECTED,
            'admin_note' => trim($data['admin_note']),
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        $mailNote = '';
        if ($ticket->email) {
            try {
                Mail::to($ticket->email)->send(new AccountTicketMail($ticket->fresh()));
                $mailNote = " Notifikasi dikirim ke {$ticket->email}.";
            } catch (\Throwable $e) {
                Log::warning('Email notifikasi penolakan tiket gagal dikirim: '.$e->getMessage());
                $mailNote = ' Namun email notifikasi gagal terkirim.';
            }
        }

        return redirect()->route('admin.tiket')->with('status', "Tiket {$ticket->ticket_code} ditolak.{$mailNote}");
    }

    /** Hapus satu riwayat tiket dari panel Admin Sekretariat. */
    public function destroy(AccountTicket $ticket): RedirectResponse
    {
        $code = $ticket->ticket_code;
        $ticket->delete();

        return back()->with('status', "Riwayat tiket {$code} berhasil dihapus.");
    }

    /** Hapus seluruh riwayat tiket yang sudah disetujui atau ditolak. */
    public function clearProcessed(): RedirectResponse
    {
        $deleted = AccountTicket::whereIn('status', [
            AccountTicket::STATUS_APPROVED,
            AccountTicket::STATUS_REJECTED,
        ])->delete();

        return redirect()->route('admin.tiket')
            ->with('status', $deleted > 0 ? "{$deleted} riwayat tiket selesai berhasil dihapus." : 'Tidak ada riwayat tiket selesai untuk dihapus.');
    }
}