@extends('layouts.app')

@section('title', 'Privasi dan izin')

@section('content')
<h1 class="rise text-3xl sm:text-4xl">Privasi &amp; izin</h1>
<p class="rise mt-1 text-ink-2" style="--i: 1">Atur akses DPA dan notifikasi pengingat.</p>

<div class="mt-10 grid gap-8 lg:grid-cols-12">
    <section class="rounded-md border border-line bg-surface p-6 lg:col-span-7" aria-labelledby="izin-dpa">
        <h2 id="izin-dpa" class="text-xl font-medium">Izin untuk DPA</h2>
        @if (! $dpa)
            <p class="mt-3 text-sm text-ink-2">DPA belum ditetapkan. Hubungi sekretariat FTII.</p>
        @else
            <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-md border border-line bg-bg p-4">
                <div>
                    <p class="font-medium">{{ $dpa->name }}</p>
                    <p class="mt-0.5 text-xs text-ink-2">
                        @if ($consent?->granted)
                            <span style="color:var(--ok);">Izin aktif</span> &middot; sejak {{ $consent->changed_at->translatedFormat('j M Y, H:i') }}
                        @elseif ($consent)
                            Izin dicabut &middot; {{ $consent->changed_at->translatedFormat('j M Y, H:i') }}
                        @else
                            Belum diizinkan
                        @endif
                    </p>
                </div>

                <form method="POST" action="{{ route('mhs.privasi.izin') }}">
                    @csrf
                    @if ($consent?->granted)
                        <input type="hidden" name="granted" value="0">
                        <button type="submit" class="btn btn-quiet btn-sm">Cabut izin</button>
                    @else
                        <input type="hidden" name="granted" value="1">
                        <button type="submit" class="btn btn-primary btn-sm">Berikan izin</button>
                    @endif
                </form>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 text-xs text-ink-2">
                <div class="rounded-md border border-line bg-bg p-3">
                    <p class="font-medium text-ink">Terlihat oleh DPA</p>
                    <p class="mt-1">Nama, NIM, skor risiko, faktor utama, dan progres 20 SKS pengayaan.</p>
                </div>
                <div class="rounded-md border border-line bg-bg p-3">
                    <p class="font-medium text-ink">Privasi terjaga</p>
                    <p class="mt-1">Berkas asli tidak disimpan; setiap akses DPA tercatat di <a href="{{ route('mhs.catatan') }}" class="text-accent hover:underline">Catatan akses</a>.</p>
                </div>
            </div>
        @endif
    </section>

    <section class="rounded-md border border-line bg-surface p-6 lg:col-span-5" aria-labelledby="pengingat">
        <h2 id="pengingat" class="text-xl font-medium">Pengingat risiko</h2>
        <form method="POST" action="{{ route('mhs.privasi.pengingat') }}" class="mt-4 space-y-4">
            @csrf
            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" name="remind_in_app" value="1" class="size-4 accent-[var(--accent)]" @checked(old('remind_in_app', $settings->remind_in_app))>
                <span>Notifikasi di aplikasi</span>
            </label>
            <label class="flex items-center gap-3 text-sm">
                <input type="checkbox" name="remind_email" value="1" class="size-4 accent-[var(--accent)]" @checked(old('remind_email', $settings->remind_email))>
                <span>Kirim ke email saat risiko Sedang/Tinggi</span>
            </label>
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" class="input" placeholder="nama@email.com" value="{{ old('email', $user->email) }}">
                @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
        </form>
    </section>
</div>

<div class="mt-8 flex flex-wrap gap-3 text-xs text-ink-3">
    <span class="rounded border border-line bg-surface px-3 py-1.5">NIM disamarkan (HMAC-SHA256)</span>
    <span class="rounded border border-line bg-surface px-3 py-1.5">Zero-PII ke API AI</span>
    <span class="rounded border border-line bg-surface px-3 py-1.5">Berkas unggahan tidak disimpan</span>
</div>
@endsection