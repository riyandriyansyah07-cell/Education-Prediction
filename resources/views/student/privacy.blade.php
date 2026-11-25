@extends('layouts.app')

@section('title', 'Privasi dan izin')

@section('content')
<div>
    <h1 class="rise">Privasi &amp; izin</h1>
    <p class="rise mt-1.5 text-sm text-ink-2" style="--i: 1">Atur akses Dosen Pembimbing Akademik (DPA) dan preferensi notifikasi pengingat.</p>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-12" style="align-items:start;">
    <section class="bg-surface lg:col-span-7" style="padding:1.4rem 1.5rem;" aria-labelledby="izin-dpa">
        <h2 id="izin-dpa" class="text-base font-semibold">Izin akses untuk DPA</h2>
        @if (! $dpa)
            <p class="mt-3 text-sm text-ink-2">DPA belum ditetapkan. Hubungi sekretariat FTII.</p>
        @else
            <div style="margin-top:1rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;border-radius:12px;border:1px solid var(--line);background:var(--bg);padding:1rem 1.15rem;">
                <div>
                    <p class="font-semibold text-ink">{{ $dpa->name }}</p>
                    <p class="mt-0.5 text-xs text-ink-2">
                        @if ($consent?->granted)
                            <span style="color:var(--ok);font-weight:600;">Izin aktif</span> &middot; sejak {{ $consent->changed_at->translatedFormat('j M Y, H:i') }}
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

            <div class="mt-4 grid gap-3 sm:grid-cols-2 text-xs text-ink-2">
                <div style="border-radius:10px;border:1px solid var(--line);background:var(--bg);padding:0.9rem 1rem;">
                    <p class="font-semibold text-ink">Terlihat oleh DPA</p>
                    <p class="mt-1" style="line-height:1.55;">Nama, NIM, skor risiko, faktor utama, serta progres 20 SKS pengayaan &amp; 4 syarat SKPI.</p>
                </div>
                <div style="border-radius:10px;border:1px solid var(--line);background:var(--bg);padding:0.9rem 1rem;">
                    <p class="font-semibold text-ink">Privasi terjaga</p>
                    <p class="mt-1" style="line-height:1.55;">Berkas asli tidak disimpan; setiap akses DPA tercatat di <a href="{{ route('mhs.catatan') }}" class="text-accent hover:underline">Catatan akses</a>.</p>
                </div>
            </div>
        @endif
    </section>

    <section class="bg-surface lg:col-span-5" style="padding:1.4rem 1.5rem;" aria-labelledby="pengingat">
        <h2 id="pengingat" class="text-base font-semibold">Pengingat risiko</h2>
        <form method="POST" action="{{ route('mhs.privasi.pengingat') }}" class="mt-4 space-y-4">
            @csrf
            <label style="display:flex;align-items:center;gap:0.65rem;padding:0.65rem 0.85rem;border-radius:10px;border:1px solid var(--line);background:var(--bg);cursor:pointer;" class="text-sm">
                <input type="checkbox" name="remind_in_app" value="1" class="size-4 accent-[var(--accent)]" @checked(old('remind_in_app', $settings->remind_in_app))>
                <span>Notifikasi di aplikasi</span>
            </label>
            <label style="display:flex;align-items:center;gap:0.65rem;padding:0.65rem 0.85rem;border-radius:10px;border:1px solid var(--line);background:var(--bg);cursor:pointer;" class="text-sm">
                <input type="checkbox" name="remind_email" value="1" class="size-4 accent-[var(--accent)]" @checked(old('remind_email', $settings->remind_email))>
                <span>Kirim ke email saat risiko Sedang/Tinggi</span>
            </label>
            <div>
                <label for="email" class="label">Email penerima pengingat</label>
                <input id="email" name="email" type="email" autocomplete="email" class="input" placeholder="nama@email.com" value="{{ old('email', $user->email) }}">
                @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-primary w-full sm:w-auto">Simpan preferensi</button>
        </form>
    </section>
</div>

<div class="mt-6 flex flex-wrap gap-2.5 text-xs text-ink-3">
    <span style="padding:0.35rem 0.75rem;border-radius:9999px;border:1px solid var(--line);background:var(--surface);">NIM disamarkan (HMAC-SHA256)</span>
    <span style="padding:0.35rem 0.75rem;border-radius:9999px;border:1px solid var(--line);background:var(--surface);">Zero-PII ke API AI</span>
    <span style="padding:0.35rem 0.75rem;border-radius:9999px;border:1px solid var(--line);background:var(--surface);">Berkas unggahan tidak disimpan</span>
</div>
@endsection