@extends('layouts.app')

@section('title', 'Ubah kata sandi')

@section('content')
<h1 class="rise text-3xl sm:text-4xl">Ubah kata sandi</h1>
<p class="rise mt-2 max-w-[60ch] text-ink-2" style="--i: 1">
    Perbarui kata sandi akunmu secara mandiri setelah masuk menggunakan sandi awal dari sekretariat.
</p>

<form method="POST" action="{{ route('sandi.update') }}" class="mt-8 max-w-md space-y-5 rounded-md border border-line bg-surface p-6">
    @csrf

    <div>
        <label for="current_password" class="label">Kata sandi saat ini</label>
        <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="input" required>
        @error('current_password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="password" class="label">Kata sandi baru <span class="font-normal text-ink-3">(min. 8 karakter)</span></label>
        <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" class="input" required>
        @error('password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="password_confirmation" class="label">Ulangi kata sandi baru</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" class="input" required>
    </div>

    <button type="submit" class="btn btn-primary w-full">Simpan Kata Sandi Baru</button>
</form>
@endsection