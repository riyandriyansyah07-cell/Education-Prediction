@extends('layouts.app')

@section('title', 'Ubah kata sandi')

@section('content')
<div>
    <h1 class="rise">Ubah kata sandi</h1>
    <p class="rise mt-1.5 max-w-[60ch] text-sm text-ink-2" style="--i: 1">
        Perbarui kata sandi akunmu secara mandiri setelah masuk menggunakan sandi awal dari sekretariat.
    </p>
</div>

<form method="POST" action="{{ route('sandi.update') }}" class="mt-6 max-w-lg bg-surface" style="padding:1.65rem 1.75rem;display:flex;flex-direction:column;gap:1.15rem;">
    @csrf

    <div>
        <label for="current_password" class="label">Kata sandi saat ini</label>
        <input id="current_password" name="current_password" type="password" autocomplete="current-password" placeholder="Masukkan kata sandi saat ini" class="input" required>
        @error('current_password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="password" class="label">Kata sandi baru <span class="font-normal text-ink-3">(min. 8 karakter)</span></label>
        <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter" class="input" required>
        @error('password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="password_confirmation" class="label">Ulangi kata sandi baru</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" placeholder="Ketik ulang kata sandi baru" class="input" required>
    </div>

    <button type="submit" class="btn btn-primary w-full mt-1" style="border-radius:9999px !important;min-height:46px;font-weight:600;">Simpan Kata Sandi Baru</button>
</form>
@endsection