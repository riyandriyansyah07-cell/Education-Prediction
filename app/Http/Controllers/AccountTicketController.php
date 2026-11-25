<?php

namespace App\Http\Controllers;

use App\Mail\AccountTicketMail;
use App\Models\AccountTicket;
use App\Models\StudentSetting;
use App\Models\User;
use App\Services\Pseudonym;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountTicketController extends Controller
{
    public const TOKEN_EXPIRY_HOURS = 24;

    /** Halaman publik pengajuan E-Ticket akun & cek status tiket (wajib 2 faktor: kode + NIM/Email). */
    public function showForm(Request $request): View
    {
        $code = strtoupper(trim((string) $request->query('kode', '')));
        $verification = trim((string) $request->query('verifikasi', ''));
        $checkedTicket = null;
        $lookupAttempted = $code !== '' || $verification !== '';
        $lookupError = null;

        if ($lookupAttempted) {
            if ($code === '' || $verification === '') {
                $lookupError = 'Masukkan kode tiket beserta NIM/ID atau email terdaftar untuk melihat status.';
            } else {
                $candidate = AccountTicket::where('ticket_code', $code)->first();
                $verifLower = strtolower($verification);

                if (
                    $candidate
                    && (
                        strtolower($candidate->username) === $verifLower
                        || strtolower((string) $candidate->email) === $verifLower
                    )
                ) {
                    $checkedTicket = $candidate;
                } else {
                    $lookupError = 'Tiket tidak ditemukan atau NIM/Email tidak cocok.';
                }
            }
        }

        return view('tickets.request', [
            'checkedTicket' => $checkedTicket,
            'searchCode' => $code,
            'searchVerification' => $verification,
            'lookupAttempted' => $lookupAttempted,
            'lookupError' => $lookupError,
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
            'email' => ['required', 'email', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'username.regex' => 'NIM atau ID pegawai hanya boleh berisi huruf, angka, titik, dan strip.',
            'email.required' => 'Email aktif wajib diisi untuk menerima tautan pengaturan akun setelah disetujui.',
            'email.email' => 'Format alamat email tidak valid.',
        ]);

        $username = trim($data['username']);
        $email = strtolower(trim($data['email']));

        // Cegah duplikasi tiket menunggu untuk username yang sama
        $existingPending = AccountTicket::where('username', $username)
            ->where('status', AccountTicket::STATUS_PENDING)
            ->first();

        if ($existingPending) {
            return redirect()->route('tiket.form')
                ->with('status', 'Masih ada tiket aktif untuk NIM/ID ini yang sedang menunggu verifikasi sekretariat. Gunakan kode tiket dan NIM/Email pada panel Cek Status.');
        }

        $ticket = $this->createTicketWithRetry([
            'request_type' => $data['request_type'],
            'name' => trim($data['name']),
            'username' => $username,
            'program_studi' => $data['request_type'] === AccountTicket::TYPE_STUDENT
                ? ($data['program_studi'] ?? null)
                : null,
            'email' => $email,
            'notes' => ($data['notes'] ?? null) ?: null,
            'status' => AccountTicket::STATUS_PENDING,
        ]);

        return redirect()->route('tiket.form')
            ->with('status', "E-Ticket {$ticket->ticket_code} berhasil dibuat! Simpan kode ini. Setelah disetujui Admin Sekretariat, tautan aman untuk mengatur kata sandi akan dikirim ke {$ticket->email}.");
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
        $processedCount = AccountTicket::whereIn('status', [
            AccountTicket::STATUS_APPROVED,
            AccountTicket::STATUS_REJECTED,
        ])->count();

        return view('admin.tickets', [
            'tickets' => $tickets,
            'status' => $status,
            'pendingCount' => $pendingCount,
            'processedCount' => $processedCount,
            'dpas' => User::where('role', User::ROLE_DPA)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /** Setujui tiket: aktifkan akun dan kirim tautan set-password sekali pakai bertoken ke email pemohon. */
    public function approve(Request $request, AccountTicket $ticket): RedirectResponse
    {
        if ($ticket->status !== AccountTicket::STATUS_PENDING) {
            return back()->with('status', 'Tiket ini sudah diproses sebelumnya.');
        }

        $data = $request->validate([
            'dpa_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_DPA),
            ],
            'admin_note' => ['nullable', 'string', 'max:300'],
        ]);

        // S3: Tiket reset kata sandi TIDAK boleh membuat akun baru bila username tidak ditemukan.
        if ($ticket->request_type === AccountTicket::TYPE_RESET) {
            $targetUser = User::where('username', $ticket->username)->first();
            if (! $targetUser) {
                return back()->withErrors([
                    'ticket' => "Akun tidak ditemukan untuk NIM/ID {$ticket->username}. Tiket reset sandi tidak dapat membuat akun baru — tolak tiket ini dan minta pemohon mengajukan tiket pembuatan akun baru.",
                ]);
            }
        }

        $rawToken = Str::random(64);
        $targetEmail = strtolower(trim((string) $ticket->email));

        DB::transaction(function () use ($ticket, $data, $request, $rawToken, &$targetEmail) {
            $existingUser = User::where('username', $ticket->username)->first();
            $placeholderPassword = Str::random(32);

            if ($ticket->request_type === AccountTicket::TYPE_RESET) {
                // Sudah diverifikasi di atas bahwa $existingUser ada
                $resolvedEmail = strtolower(trim((string) ($existingUser->email ?: $ticket->email)));
                $existingUser->update([
                    'email' => $resolvedEmail ?: $existingUser->email,
                    'is_active' => true,
                ]);
                $targetEmail = $resolvedEmail ?: $targetEmail;
            } else {
                $role = $ticket->request_type === AccountTicket::TYPE_DPA ? User::ROLE_DPA : User::ROLE_STUDENT;
                if (! $existingUser) {
                    $ref = $role === User::ROLE_STUDENT ? Pseudonym::ref($ticket->username) : null;
                    User::create([
                        'name' => $ticket->name,
                        'username' => $ticket->username,
                        'email' => $ticket->email,
                        'role' => $role,
                        'password' => $placeholderPassword,
                        'student_ref' => $ref,
                        'dpa_id' => $role === User::ROLE_STUDENT && ! empty($data['dpa_id']) ? (int) $data['dpa_id'] : null,
                        'is_active' => true,
                    ]);
                    if ($ref) {
                        StudentSetting::firstOrCreate(['student_ref' => $ref]);
                    }
                } else {
                    $resolvedEmail = strtolower(trim((string) ($existingUser->email ?: $ticket->email)));
                    $existingUser->update([
                        'email' => $resolvedEmail ?: $existingUser->email,
                        'is_active' => true,
                    ]);
                    $targetEmail = $resolvedEmail ?: $targetEmail;
                }
            }

            if ($targetEmail !== '') {
                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $targetEmail],
                    [
                        'token' => Hash::make($rawToken),
                        'created_at' => now(),
                    ]
                );
            }

            $ticket->update([
                'status' => AccountTicket::STATUS_APPROVED,
                'admin_note' => ($data['admin_note'] ?? null) ?: 'Akun telah aktif dan tautan pengaturan kata sandi sekali pakai sudah dikirim ke email pemohon.',
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);
        });

        $mailNote = '';
        if ($targetEmail !== '') {
            $setPasswordUrl = route('sandi.atur.form', [
                'token' => $rawToken,
                'email' => $targetEmail,
            ]);

            try {
                Mail::to($targetEmail)->send(new AccountTicketMail(
                    $ticket->fresh(),
                    $setPasswordUrl,
                    self::TOKEN_EXPIRY_HOURS,
                ));
                $mailNote = " Tautan pengaturan kata sandi sekali pakai (berlaku ".self::TOKEN_EXPIRY_HOURS." jam) telah dikirim ke {$targetEmail}.";
            } catch (\Throwable $e) {
                Log::warning('Email notifikasi persetujuan tiket gagal dikirim: '.$e->getMessage());
                $mailNote = ' Namun email gagal terkirim — cek pengaturan SMTP di .env.';
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

    /**
     * Buat tiket dengan kode acak 10 karakter (23 karakter total, sesuai kolom varchar(24))
     * dan retry otomatis bila terjadi tabrakan unique constraint.
     *
     * @param  array<string,mixed>  $attributes
     */
    private function createTicketWithRetry(array $attributes, int $maxAttempts = 5): AccountTicket
    {
        $attempt = 0;

        while (true) {
            $attempt++;
            $ticketCode = 'TKT-'.now()->format('Ymd').'-'.strtoupper(Str::random(10));

            try {
                return AccountTicket::create($attributes + ['ticket_code' => $ticketCode]);
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
            } catch (QueryException $e) {
                $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
                if (! in_array($sqlState, ['23000', '23505'], true) || $attempt >= $maxAttempts) {
                    throw $e;
                }
            }
        }
    }
}