<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    public function show(): View
    {
        return view('auth.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $request->user()->update([
            'password' => $data['password'],
        ]);

        return back()->with('status', 'Kata sandi berhasil diperbarui.');
    }

    /** Halaman publik untuk mengatur kata sandi baru melalui tautan bertoken sekali pakai (S1). */
    public function showSetup(Request $request, string $token): View
    {
        return view('auth.setup-password', [
            'token' => $token,
            'email' => strtolower(trim((string) $request->query('email', ''))),
        ]);
    }

    /** Verifikasi token sekali pakai dari password_reset_tokens, simpan kata sandi baru, lalu hapus token. */
    public function completeSetup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:128'],
            'email' => ['required', 'email', 'max:120'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ], [
            'email.required' => 'Email akun wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $email = strtolower(trim($data['email']));
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record || ! Hash::check($data['token'], (string) $record->token)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Tautan pengaturan kata sandi tidak valid atau sudah digunakan.']);
        }

        $createdAt = $record->created_at ? Carbon::parse($record->created_at) : null;
        if (! $createdAt || $createdAt->lt(now()->subHours(AccountTicketController::TOKEN_EXPIRY_HOURS))) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Tautan pengaturan kata sandi sudah kedaluwarsa. Ajukan tiket reset baru ke sekretariat.']);
        }

        $user = User::where('email', $email)->where('is_active', true)->first();
        if (! $user) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Akun aktif dengan email tersebut tidak ditemukan.']);
        }

        DB::transaction(function () use ($user, $data, $email) {
            $user->update([
                'password' => $data['password'],
            ]);
            DB::table('password_reset_tokens')->where('email', $email)->delete();
        });

        return redirect()->route('login')
            ->with('status', 'Kata sandi berhasil diatur. Silakan masuk menggunakan NIM/ID dan kata sandi barumu.');
    }
}