<?php

namespace App\Http\Controllers;

use App\Models\AccountTicket;
use App\Models\Reminder;
use App\Models\SkpiDocument;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NotificationPollController extends Controller
{
    /**
     * Endpoint ringan untuk polling notifikasi browser & update badge sidebar secara real-time.
     */
    public function check(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = [];

        if ($user->isAdmin()) {
            $pendingTickets = AccountTicket::where('status', AccountTicket::STATUS_PENDING)->count();
            $latestTicket = AccountTicket::where('status', AccountTicket::STATUS_PENDING)
                ->latest('id')
                ->first();

            $pendingSkpi = Schema::hasTable('skpi_documents')
                ? SkpiDocument::where('verification_status', SkpiDocument::STATUS_MENUNGGU)->count()
                : 0;
            $latestSkpi = Schema::hasTable('skpi_documents')
                ? SkpiDocument::where('verification_status', SkpiDocument::STATUS_MENUNGGU)->latest('id')->first()
                : null;

            if ($pendingTickets > 0 && $latestTicket) {
                $items[] = [
                    'key' => 'ticket-'.$latestTicket->id,
                    'title' => 'E-Ticket Pengajuan Akun Baru',
                    'body' => "{$latestTicket->name} ({$latestTicket->username}) · {$latestTicket->typeLabel()}. Total {$pendingTickets} tiket menunggu.",
                    'url' => route('admin.tiket'),
                ];
            }

            if ($pendingSkpi > 0 && $latestSkpi) {
                $items[] = [
                    'key' => 'skpi-'.$latestSkpi->id,
                    'title' => 'Bukti SKPI Menunggu Verifikasi',
                    'body' => "Ada {$pendingSkpi} berkas bukti SKPI menunggu pemeriksaan Sekretariat/DPA.",
                    'url' => route('admin.skpi'),
                ];
            }

            return response()->json([
                'role' => 'admin',
                'pending_tickets' => $pendingTickets,
                'pending_skpi' => $pendingSkpi,
                'items' => $items,
            ]);
        }

        if ($user->isDpa()) {
            $pendingSkpi = 0;
            $latestSkpi = null;
            if (Schema::hasTable('skpi_documents')) {
                $refs = User::where('role', User::ROLE_STUDENT)
                    ->where('dpa_id', $user->id)
                    ->whereNotNull('student_ref')
                    ->pluck('student_ref');

                if ($refs->isNotEmpty()) {
                    $pendingSkpi = SkpiDocument::whereIn('student_ref', $refs)
                        ->where('verification_status', SkpiDocument::STATUS_MENUNGGU)
                        ->count();
                    $latestSkpi = SkpiDocument::whereIn('student_ref', $refs)
                        ->where('verification_status', SkpiDocument::STATUS_MENUNGGU)
                        ->latest('id')
                        ->first();
                }
            }

            if ($pendingSkpi > 0 && $latestSkpi) {
                $items[] = [
                    'key' => 'dpa-skpi-'.$latestSkpi->id,
                    'title' => 'Bukti SKPI Mahasiswa Bimbingan',
                    'body' => "Ada {$pendingSkpi} berkas bukti SKPI mahasiswa bimbingan yang menunggu verifikasi.",
                    'url' => route('dpa.bimbingan'),
                ];
            }

            return response()->json([
                'role' => 'dpa',
                'pending_skpi' => $pendingSkpi,
                'items' => $items,
            ]);
        }

        if ($user->isStudent() && $user->student_ref) {
            $latestReminder = Reminder::where('student_ref', $user->student_ref)
                ->whereNull('read_at')
                ->latest('id')
                ->first();

            if ($latestReminder) {
                $items[] = [
                    'key' => 'reminder-'.$latestReminder->id,
                    'title' => 'Pengingat Risiko Akademik',
                    'body' => (string) $latestReminder->message,
                    'url' => route('mhs.ringkasan'),
                ];
            }

            return response()->json([
                'role' => 'mahasiswa',
                'items' => $items,
            ]);
        }

        return response()->json([
            'role' => $user->role,
            'items' => [],
        ]);
    }
}
