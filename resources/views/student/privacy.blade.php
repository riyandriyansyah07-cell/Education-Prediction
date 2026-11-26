@extends('layouts.app')

@section('title', 'Privasi dan pengingat')

@section('content')
<div>
    <h1 class="rise">Privasi &amp; pengingat</h1>
    <p class="rise mt-1.5 text-sm text-ink-2" style="--i: 1">Informasi Dosen Pembimbing Akademik (DPA) dan preferensi notifikasi pengingat.</p>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-12" style="align-items:start;">
    <section class="bg-surface lg:col-span-7" style="padding:1.4rem 1.5rem;" aria-labelledby="izin-dpa">
        <h2 id="izin-dpa" class="text-base font-semibold">Dosen Pembimbing Akademik</h2>
        @if (! $dpa)
            <p class="mt-3 text-sm text-ink-2">DPA belum ditetapkan. Hubungi sekretariat FTII.</p>
        @else
            <div style="margin-top:1rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.85rem;border-radius:12px;border:1px solid var(--line);background:var(--bg);padding:1rem 1.15rem;">
                <div style="min-width:0;">
                    <p class="font-semibold text-ink">{{ $dpa->name }}</p>
                    <p class="mt-0.5 text-xs text-ink-2">
                        Dosen Pembimbing Akademik pendamping
                        @if ($dpa->email)
                            &middot; <span class="mono">{{ $dpa->email }}</span>
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-ink-3">
                        Status tindak lanjut: <strong class="text-ink-2">{{ $settings->followUpLabel() }}</strong>
                        @if ($settings->last_guidance_at)
                            &middot; Bimbingan terakhir: <strong class="text-ink-2">{{ $settings->last_guidance_at->translatedFormat('j M Y') }}</strong>
                        @endif
                    </p>
                </div>

                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem;">
                    @if ($dpa->email)
                        <a href="mailto:{{ $dpa->email }}?subject={{ rawurlencode('Konsultasi Bimbingan Akademik - '.$user->name.' ('.$user->username.')') }}"
                           class="btn btn-quiet btn-sm" style="font-size:12px;">
                            Hubungi via Email
                        </a>
                    @endif
                    <span class="mono" style="padding:0.3rem 0.75rem;border-radius:9999px;font-size:11px;font-weight:600;color:var(--ok);border:1px solid color-mix(in srgb, var(--ok) 40%, var(--line));background:color-mix(in srgb, var(--ok) 10%, var(--surface));">
                        Aktif
                    </span>
                </div>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 text-xs text-ink-2">
                <div style="border-radius:10px;border:1px solid var(--line);background:var(--bg);padding:0.9rem 1rem;">
                    <p class="font-semibold text-ink">Terlihat oleh DPA</p>
                    <p class="mt-1" style="line-height:1.55;">Nama, NIM, skor risiko, faktor utama, serta progres 20 SKS pengayaan &amp; bukti 4 syarat SKPI.</p>
                </div>
                <div style="border-radius:10px;border:1px solid var(--line);background:var(--bg);padding:0.9rem 1rem;">
                    <p class="font-semibold text-ink">Privasi terjaga</p>
                    <p class="mt-1" style="line-height:1.55;">File impor KHS tidak disimpan; bukti SKPI disimpan privat dan setiap akses tercatat di <a href="{{ route('mhs.catatan') }}" class="text-accent hover:underline">Catatan akses</a>.</p>
                </div>
            </div>

            @php $guidanceNotes = $guidanceNotes ?? collect(); @endphp
            <div style="margin-top:1.25rem;padding-top:1.15rem;border-top:1px solid var(--line);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
                    <h3 class="text-sm font-semibold text-ink">Catatan Bimbingan Terbaru dari DPA</h3>
                    <span class="mono text-xs text-ink-3">{{ $guidanceNotes->count() }} terbaru</span>
                </div>
                @if ($guidanceNotes->isEmpty())
                    <p class="mt-2 text-xs text-ink-3">Belum ada catatan bimbingan yang dicatat oleh DPA.</p>
                @else
                    <ul style="list-style:none;margin:0.75rem 0 0;padding:0;display:flex;flex-direction:column;gap:0.65rem;">
                        @foreach ($guidanceNotes as $gn)
                            <li style="border-radius:10px;border:1px solid var(--line);background:var(--bg);padding:0.85rem 1rem;">
                                <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.35rem 0.75rem;">
                                    <span class="text-sm font-medium text-ink">{{ $gn->topic }}</span>
                                    <span class="mono text-xs text-ink-3">
                                        {{ $gn->guidance_date?->translatedFormat('j M Y') }} &middot; {{ $gn->followUpLabel() }}
                                    </span>
                                </div>
                                <p class="mt-1.5 text-xs text-ink-2" style="line-height:1.55;">{{ $gn->follow_up_action }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
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
    <span style="padding:0.35rem 0.75rem;border-radius:9999px;border:1px solid var(--line);background:var(--surface);">File impor KHS tidak disimpan</span>
    <span style="padding:0.35rem 0.75rem;border-radius:9999px;border:1px solid var(--line);background:var(--surface);">Bukti SKPI tersimpan privat</span>
</div>
@endsection