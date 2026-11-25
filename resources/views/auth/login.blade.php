@extends('layouts.base')

@section('title', 'Masuk')

@section('body')
<style>
    .lg-shell {
        min-height: 100dvh;
        display: grid;
        grid-template-columns: 1fr;
        background: var(--bg);
    }
    @media (min-width: 1024px) {
        .lg-shell { grid-template-columns: minmax(420px, 5fr) 7fr; }
    }

    .lg-left {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 1.75rem 1.5rem;
        background: var(--bg);
        position: relative;
        z-index: 2;
    }
    @media (min-width: 640px) { .lg-left { padding: 2.25rem 2.75rem; } }
    @media (min-width: 1024px) { .lg-left { padding: 2.75rem 3.5rem; } }

    .lg-brand {
        display: inline-flex;
        align-items: center;
        gap: 0.7rem;
        text-decoration: none;
        color: var(--ink);
    }
    .lg-brand-mark {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        background: color-mix(in oklab, var(--accent) 14%, var(--surface));
        border: 1px solid color-mix(in oklab, var(--accent) 32%, var(--line));
        color: var(--accent);
        flex-shrink: 0;
    }
    .lg-brand-title { font-weight: 600; font-size: 0.96rem; letter-spacing: -0.02em; line-height: 1.2; }
    .lg-brand-sub { font-size: 0.72rem; color: var(--ink-3); letter-spacing: 0.02em; }

    .lg-form-wrap {
        width: 100%;
        max-width: 24.5rem;
        margin: 2.75rem auto;
    }
    @media (min-width: 1024px) { .lg-form-wrap { margin: 2.5rem 0; } }

    .lg-pwd-wrap { position: relative; }
    .lg-pwd-wrap .input { padding-right: 2.75rem; }
    .lg-pwd-toggle {
        position: absolute;
        top: 50%;
        right: 0.5rem;
        transform: translateY(-50%);
        width: 32px;
        height: 32px;
        border: 0;
        background: transparent;
        color: var(--ink-3);
        border-radius: 6px;
        display: grid;
        place-items: center;
        cursor: pointer;
        transition: color 140ms ease, background-color 140ms ease;
    }
    .lg-pwd-toggle:hover { color: var(--ink); background: var(--sunken); }

    .lg-ticket-box {
        margin-top: 1.5rem;
        padding: 0.9rem 1rem;
        border-radius: 10px;
        border: 1px solid var(--line);
        background: var(--surface);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .lg-right {
        position: relative;
        overflow: hidden;
        border-top: 1px solid var(--line);
        background: var(--surface);
        padding: 2.5rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    @media (min-width: 640px) { .lg-right { padding: 3rem 2.75rem; } }
    @media (min-width: 1024px) {
        .lg-right {
            border-top: 0;
            border-left: 1px solid var(--line);
            padding: 3.5rem 4rem;
        }
    }

    .lg-right-bg {
        position: absolute;
        inset: 0;
        pointer-events: none;
        background-image:
            radial-gradient(680px circle at 85% 15%, color-mix(in oklab, var(--accent) 14%, transparent), transparent 65%),
            radial-gradient(520px circle at 15% 88%, color-mix(in oklab, var(--ok) 11%, transparent), transparent 62%),
            radial-gradient(color-mix(in oklab, var(--ink) 12%, transparent) 1px, transparent 1px);
        background-size: 100% 100%, 100% 100%, 24px 24px;
    }

    .lg-showcase {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 34rem;
    }

    .lg-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--accent);
        background: color-mix(in oklab, var(--accent) 10%, var(--bg));
        border: 1px solid color-mix(in oklab, var(--accent) 28%, var(--line));
    }
    .lg-pill-dot {
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: var(--ok);
    }

    .lg-preview {
        margin-top: 2rem;
        border-radius: 14px;
        border: 1px solid var(--line-strong);
        background: var(--bg);
        overflow: hidden;
        box-shadow: 0 22px 48px -20px rgba(15, 23, 42, 0.22);
    }
    .lg-preview-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.65rem 1rem;
        background: var(--sunken);
        border-bottom: 1px solid var(--line);
        font-size: 0.74rem;
        color: var(--ink-3);
    }
    .lg-dots { display: flex; gap: 6px; }
    .lg-dots span { width: 9px; height: 9px; border-radius: 999px; background: var(--line-strong); }

    .lg-preview-body { padding: 1.25rem; }
    .lg-meter {
        margin-top: 0.9rem;
        height: 8px;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--ok) 0%, var(--ok) 28%, var(--warn) 28%, var(--warn) 58%, var(--bad) 58%, var(--bad) 100%);
        opacity: 0.85;
        position: relative;
    }
    .lg-meter-pin {
        position: absolute;
        top: -4px;
        left: 16%;
        width: 3px;
        height: 16px;
        border-radius: 2px;
        background: var(--ink);
    }

    .lg-metrics {
        margin-top: 1.1rem;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.65rem;
    }
    .lg-metric {
        padding: 0.7rem 0.75rem;
        border-radius: 10px;
        border: 1px solid var(--line);
        background: var(--surface);
    }
    .lg-metric-label { font-size: 0.7rem; color: var(--ink-3); }
    .lg-metric-val { margin-top: 0.2rem; font-size: 0.84rem; font-weight: 600; color: var(--ink); }

    .lg-points {
        margin-top: 1.75rem;
        display: grid;
        gap: 0.85rem;
    }
    .lg-point {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }
    .lg-point-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        border: 1px solid var(--line);
        background: var(--bg);
        color: var(--accent);
        display: grid;
        place-items: center;
        flex-shrink: 0;
        margin-top: 1px;
    }
</style>

<div class="lg-shell">
    <section class="lg-left">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('landing') }}" class="lg-brand">
                <x-brand-logo :size="36" />
                <span>
                    <span class="lg-brand-title" style="display:block;">Cegah Sebelum Terlambat</span>
                    <span class="lg-brand-sub" style="display:block;">FTII UHAMKA</span>
                </span>
            </a>
            <button type="button" class="btn btn-quiet btn-sm" data-theme-toggle aria-label="Ganti tema terang atau gelap">
                <x-icon name="sun" class="hidden dark:block" /><x-icon name="moon" class="dark:hidden" />
            </button>
        </div>

        <div class="lg-form-wrap">
            <h1 class="rise text-3xl sm:text-4xl" style="letter-spacing:-0.03em;">Masuk ke akunmu</h1>
            <p class="rise mt-2 text-sm text-ink-2" style="--i: 1">Gunakan NIM untuk mahasiswa, atau ID pegawai untuk DPA dan admin.</p>

            <form id="login-form" method="POST" action="{{ route('login.store') }}" class="rise mt-7 space-y-5" style="--i: 2" novalidate>
                @csrf
                <div>
                    <label for="username" class="label">NIM atau ID pegawai</label>
                    <input id="username" name="username" type="text" inputmode="text" autocomplete="username" required autofocus
                           value="{{ old('username') }}" placeholder="Contoh: 2303045009" class="input mono" aria-describedby="@error('username') username-error @enderror">
                    @error('username')<p id="username-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="label">Kata sandi</label>
                    <div class="lg-pwd-wrap">
                        <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Masukkan kata sandi" class="input">
                        <button type="button" class="lg-pwd-toggle" id="toggle-pwd" aria-label="Tampilkan atau sembunyikan kata sandi">
                            <svg id="eye-open" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg id="eye-closed" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6.5 0-10-7-10-7a21.77 21.77 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A10.94 10.94 0 0 1 12 5c6.5 0 10 7 10 7a21.8 21.8 0 0 1-3.17 4.49"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn btn-primary h-11 w-full">Masuk</button>
            </form>

            <div class="lg-ticket-box">
                <div style="min-width:0;">
                    <p class="text-xs font-medium text-ink">Belum punya akun atau lupa sandi?</p>
                    <p class="text-xs text-ink-3" style="margin-top:2px;">Aktivasi dikirim otomatis ke emailmu.</p>
                </div>
                <a href="{{ route('tiket.form') }}" class="btn btn-quiet btn-sm" style="white-space:nowrap;">Ajukan E-Ticket &rarr;</a>
            </div>

            <p class="mt-4 text-xs text-ink-3">Sesi otomatis berakhir setelah 30 menit tanpa aktivitas demi keamanan akun.</p>
        </div>

        <div class="flex items-center justify-between gap-3 text-xs text-ink-3">
            <span>Capstone Project Kelompok 12 &middot; FTII UHAMKA</span>
            <a href="{{ route('landing') }}" class="hover:underline">Beranda</a>
        </div>
    </section>

    <aside class="lg-right">
        <div class="lg-right-bg" aria-hidden="true"></div>
        <div class="lg-showcase">
            @if ($demo->isNotEmpty())
                <h2 class="text-2xl">Akun contoh</h2>
                <p class="mt-2 text-ink-2">Tampil hanya di lingkungan lokal. Semua data sintetis. Kata sandi: <span class="mono">password123</span></p>
                <ul class="mt-6 divide-y divide-line border-y border-line">
                    @foreach ($demo as $d)
                        <li class="flex items-center justify-between gap-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ $d->name }}</p>
                                <p class="text-sm text-ink-2">{{ $d->roleLabel() }}<span class="mono ml-2 text-ink-3">{{ $d->username }}</span></p>
                            </div>
                            <button type="button" class="btn btn-quiet btn-sm" data-fill="{{ $d->username }}" data-password="password123">Isi</button>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-5 text-sm text-ink-3">Akun mahasiswa pertama belum punya data, jadi alur impor bisa dicoba dari awal.</p>
            @else
                <span class="lg-pill">
                    <span class="lg-pill-dot" aria-hidden="true"></span>
                    Sistem Peringatan Dini Akademik
                </span>

                <h2 class="mt-4 text-3xl sm:text-4xl" style="letter-spacing:-0.03em; line-height:1.15;">
                    Kendali tetap di tanganmu.
                </h2>
                <p class="mt-3 text-sm text-ink-2" style="max-width:46ch; line-height:1.65;">
                    Pantau risiko nilai semester lebih awal, hitung target UAS minimal, dan kawal progres 20 SKS Pengayaan dalam satu tempat.
                </p>

                <div class="lg-preview" aria-hidden="true">
                    <div class="lg-preview-bar">
                        <div class="lg-dots"><span></span><span></span><span></span></div>
                        <span class="mono">peringatandini.online/mahasiswa/ringkasan</span>
                    </div>
                    <div class="lg-preview-body">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs text-ink-3">Status Risiko Semester Berjalan</p>
                                <p class="mt-0.5 text-xl font-semibold" style="color:var(--ok);">Risiko rendah</p>
                            </div>
                            <span class="mono text-lg font-semibold text-ink">1,2 <span class="text-xs font-normal text-ink-3">/ 10</span></span>
                        </div>
                        <div class="lg-meter"><span class="lg-meter-pin"></span></div>
                        <div class="lg-metrics">
                            <div class="lg-metric">
                                <p class="lg-metric-label">Target UAS</p>
                                <p class="lg-metric-val mono">C: 56 &middot; B: 68</p>
                            </div>
                            <div class="lg-metric">
                                <p class="lg-metric-label">MK Pengayaan</p>
                                <p class="lg-metric-val">20 SKS + PKM</p>
                            </div>
                            <div class="lg-metric">
                                <p class="lg-metric-label">Akses DPA</p>
                                <p class="lg-metric-val" style="color:var(--ok);">Izin Penuhmu</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg-points">
                    <div class="lg-point">
                        <span class="lg-point-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-ink">Privasi terjaga penuh</p>
                            <p class="text-xs text-ink-2">DPA hanya bisa melihat hasilmu setelah kamu mengizinkan, dan izin bisa dicabut kapan saja.</p>
                        </div>
                    </div>
                    <div class="lg-point">
                        <span class="lg-point-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-ink">Deteksi dini berbasis data</p>
                            <p class="text-xs text-ink-2">Menghitung kehadiran, nilai berjalan, dan progres MK Pengayaan beserta saran langkah perbaikan.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('toggle-pwd');
    var input = document.getElementById('password');
    var openIcon = document.getElementById('eye-open');
    var closedIcon = document.getElementById('eye-closed');
    if (!btn || !input) return;
    btn.addEventListener('click', function () {
        var isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        if (openIcon && closedIcon) {
            openIcon.style.display = isHidden ? 'none' : '';
            closedIcon.style.display = isHidden ? '' : 'none';
        }
    });
});
</script>
@endsection