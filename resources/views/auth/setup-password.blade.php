@extends('layouts.base')

@section('title', 'Atur kata sandi baru')

@section('body')
<header class="sticky top-0 z-30 border-b border-line bg-bg/90 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4 sm:px-8">
        <a href="{{ route('landing') }}" class="font-semibold tracking-tight">Cegah Sebelum Terlambat</a>
        <a href="{{ route('login') }}" class="btn btn-quiet btn-sm">Kembali ke Masuk</a>
    </div>
</header>

<main class="mx-auto max-w-md px-4 py-12 sm:px-8">
    <h1 class="rise text-3xl">Atur kata sandi baru</h1>
    <p class="rise mt-2 text-sm text-ink-2" style="--i: 1">
        Buat kata sandi pribadi untuk akunmu. Tautan ini hanya berlaku satu kali dan akan kedaluwarsa otomatis setelah 24 jam.
    </p>

    <form method="POST" action="{{ route('sandi.atur.store') }}" class="mt-8 space-y-5 rounded-md border border-line bg-surface p-6">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="label">Email terdaftar</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" class="input" required>
            @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
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

        <button type="submit" class="btn btn-primary w-full">Simpan Kata Sandi &amp; Lanjut Masuk</button>
    </form>
</main>
@endsection
