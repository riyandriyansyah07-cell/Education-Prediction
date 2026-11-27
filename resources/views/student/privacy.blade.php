@extends('layouts.app')

@section('title', 'Privasi dan pengingat')

@section('content')
<div>
    <h1 class="rise">Privasi &amp; pengingat</h1>
    <p class="rise mt-1.5 text-sm text-ink-2" style="--i: 1">Informasi Dosen Pembimbing Akademik (DPA) dan preferensi notifikasi pengingat.</p>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-12" style="align-items:start;">
    <section class="bg-surface lg:col-span-7" style="padding:1.55rem 1.65rem;" aria-labelledby="izin-dpa">
        <h2 id="izin-dpa" class="text-base font-semibold text-ink">Dosen Pembimbing Akademik</h2>
        @if (! $dpa)
            <p class="mt-3 text-sm text-ink-2">DPA belum ditetapkan. Hubungi sekretariat FTII.</p>
        @else
            <div style="margin-top:1rem;border-radius:14px;background:var(--sunken);border:1px solid var(--line);padding:1.1rem 1.2rem;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;">
                    <div style="min-width:0;">
                        <p class="text-base font-semibold text-ink" style="margin:0;">{{ $dpa->name }}</p>
                        <p class="mt-1 text-xs text-ink-2" style="margin-bottom:0;line-height:1.5;">
                            Dosen Pembimbing Akademik pendamping
                            @if ($dpa->email)
                                &middot; <span class="mono">{{ $dpa->email }}</span>
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-ink-3" style="margin-bottom:0;line-height:1.5;">
                            Status tindak lanjut: <strong class="text-ink">{{ $settings->followUpLabel() }}</strong>
                            @if ($settings->last_guidance_at)
                                &middot; Bimbingan terakhir: <strong class="text-ink">{{ $settings->last_guidance_at->translatedFormat('j M Y') }}</strong>
                            @endif
                        </p>
                    </div>

                    <span class="mono" style="font-size:12px;font-weight:700;color:var(--ok);flex-shrink:0;">
                        Aktif
                    </span>
                </div>

                @if ($dpa->email)
                    <div class="mt-3.5">
                        <a href="mailto:{{ $dpa->email }}?subject={{ rawurlencode('Konsultasi Bimbingan Akademik - '.$user->name.' ('.$user->username.')') }}"
                           class="btn btn-quiet btn-sm" style="border-radius:9999px !important;font-size:12px;padding:0.4rem 0.9rem;">
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="4.5" width="15" height="11" rx="2"/><path d="M3.5 6l6.5 4.5L16.5 6"/></svg>
                            <span>Hubungi via Email</span>
                        </a>
                    </div>
                @endif
            </div>

            <div class="mt-5 grid gap-5 sm:grid-cols-2 text-xs text-ink-2" style="padding-top:1.15rem;border-top:1px solid var(--line);">
                <div>
                    <p class="font-semibold text-ink">Terlihat oleh DPA</p>
                    <p class="mt-1.5" style="line-height:1.6;">Nama, NIM, skor risiko, faktor utama, serta progres 20 SKS pengayaan &amp; bukti 4 syarat SKPI.</p>
                </div>
                <div>
                    <p class="font-semibold text-ink">Privasi terjaga</p>
                    <p class="mt-1.5" style="line-height:1.6;">File impor KHS tidak disimpan; bukti SKPI disimpan privat dan setiap akses tercatat di <a href="{{ route('mhs.catatan') }}" class="font-medium text-accent hover:underline">Catatan akses</a>.</p>
                </div>
            </div>

            @php $guidanceNotes = $guidanceNotes ?? collect(); @endphp
            <div style="margin-top:1.35rem;padding-top:1.25rem;border-top:1px solid var(--line);">
                <div style="display:flex;align-items:baseline;justify-content:space-between;gap:0.5rem;">
                    <h3 class="text-sm font-semibold text-ink">Catatan Bimbingan Terbaru dari DPA</h3>
                    <span class="mono text-xs text-ink-3">{{ $guidanceNotes->count() }} terbaru</span>
                </div>
                @if ($guidanceNotes->isEmpty())
                    <p class="mt-2.5 text-xs text-ink-3">Belum ada catatan bimbingan yang dicatat oleh DPA.</p>
                @else
                    <ul style="list-style:none;margin:0.85rem 0 0;padding:0;display:flex;flex-direction:column;">
                        @foreach ($guidanceNotes as $gn)
                            <li style="padding:0.9rem 0;{{ ! $loop->last ? 'border-bottom:1px solid var(--line);' : 'padding-bottom:0.25rem;' }}">
                                <div style="display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:0.3rem 0.75rem;">
                                    <span class="text-sm font-semibold text-ink">{{ $gn->topic }}</span>
                                    <span class="mono text-xs text-ink-3">
                                        {{ $gn->guidance_date?->translatedFormat('j M Y') }} &middot; {{ $gn->followUpLabel() }}
                                    </span>
                                </div>
                                <p class="mt-1.5 text-xs text-ink-2" style="line-height:1.6;margin-bottom:0;">{{ $gn->follow_up_action }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <p class="mt-5 text-xs text-ink-3" style="padding-top:1rem;border-top:1px solid var(--line);line-height:1.6;margin-bottom:0;">
            NIM disamarkan (HMAC-SHA256) &middot; Zero-PII ke API AI &middot; File impor KHS tidak disimpan &middot; Bukti SKPI tersimpan privat
        </p>
    </section>

    <section class="bg-surface lg:col-span-5" style="padding:1.55rem 1.65rem;" aria-labelledby="pengingat">
        <h2 id="pengingat" class="text-base font-semibold text-ink">Pengingat risiko</h2>
        <p class="mt-1 text-xs text-ink-3" style="line-height:1.55;">Atur bagaimana sistem mengingatkan perubahan skor risiko atau kehadiran di bawah 75%.</p>

        <form method="POST" action="{{ route('mhs.privasi.pengingat') }}" class="mt-4 space-y-3.5">
            @csrf
            <label style="display:flex;align-items:center;gap:0.7rem;padding:0.75rem 0.95rem;border-radius:12px;border:1px solid var(--line);background:var(--sunken);cursor:pointer;" class="text-sm">
                <input type="checkbox" name="remind_in_app" value="1" class="size-4 accent-[var(--accent)]" @checked(old('remind_in_app', $settings->remind_in_app))>
                <span>Notifikasi di aplikasi</span>
            </label>
            <label style="display:flex;align-items:center;gap:0.7rem;padding:0.75rem 0.95rem;border-radius:12px;border:1px solid var(--line);background:var(--sunken);cursor:pointer;" class="text-sm">
                <input type="checkbox" name="remind_email" value="1" class="size-4 accent-[var(--accent)]" @checked(old('remind_email', $settings->remind_email))>
                <span>Kirim ke email saat risiko Sedang/Tinggi</span>
            </label>
            <div style="padding-top:0.25rem;">
                <label for="email" class="label">Email penerima pengingat</label>
                <input id="email" name="email" type="email" autocomplete="email" class="input" placeholder="nama@uhamka.ac.id" value="{{ old('email', $user->email) }}">
                @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-primary w-full" style="border-radius:9999px !important;min-height:46px;font-weight:600;">Simpan preferensi</button>
        </form>
    </section>
</div>
@endsection