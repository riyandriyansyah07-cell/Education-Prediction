<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
}