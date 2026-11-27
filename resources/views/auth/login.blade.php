@extends('layouts.base')

@section('title', 'Masuk')

@section('body')
<style>
    .lg-shell {
        min-height: 100vh;
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        background: var(--surface);
    }
    @media (min-width: 980px) {
        .lg-shell {
            grid-template-columns: minmax(0, 470px) minmax(0, 1fr);
        }
    }
    .lg-left {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 2rem clamp(1.35rem, 4vw, 3rem);
        background: var(--surface);
    }
    .lg-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .lg-brand {
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
        text-decoration: none;
        color: var(--ink);
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: -0.015em;
    }
    .lg-form-wrap {
        width: 100%;
        max-width: 390px;
        margin: 2.25rem auto;
    }
    .lg-eyebrow {
        font-size: 12px;
        font-weight: 600;
        color: var(--accent);
        margin-bottom: 0.45rem;
    }
    .lg-title {
        font-size: clamp(1.75rem, 3vw, 2.15rem);
        font-weight: 750;
        letter-spacing: -0.03em;
        line-height: 1.15;
        color: var(--ink);
    }
    .lg-sub {
        margin-top: 0.6rem;
        font-size: 0.9rem;
        color: var(--ink-2);
        line-height: 1.55;
    }
    .lg-pass-wrap {
        position: relative;
    }
    .lg-pass-wrap .input {
        padding-right: 2.75rem;
    }
    .lg-pass-toggle {
        position: absolute;
        right: 0.55rem;
        top: 50%;
        transform: translateY(-50%);
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--ink-3);
        background: transparent;
        border: none;
        cursor: pointer;
    }
    .lg-pass-toggle:hover { color: var(--ink); }
    .lg-submit {
        width: 100%;
        min-height: 46px;
        padding: 0.55rem 0.6rem 0.55rem 1.25rem;
        border-radius: 9999px;
        background: var(--ink);
        color: var(--surface);
        font-size: 0.92rem;
        font-weight: 600;
        border: 1px solid var(--ink);
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        transition: opacity 140ms ease;
    }
    .lg-submit:hover { opacity: 0.9; }
    .lg-submit-circle {
        width: 32px;
        height: 32px;
        border-radius: 9999px;
        background: var(--surface);
        color: var(--ink);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .lg-ticket-box {
        margin-top: 1.35rem;
        padding: 0.95rem 1.1rem;
        border-radius: 16px;
        border: 1px solid var(--line);
        background: var(--sunken);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .lg-foot {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--line);
        font-size: 12px;
        color: var(--ink-3);
    }

    /* Right editorial panel */
    .lg-right-wrap {
        display: none;
        padding: 1.25rem 1.25rem 1.25rem 0;
        background: var(--surface);
    }
    @media (min-width: 980px) {
        .lg-right-wrap { display: flex; }
    }
    .lg-right {
        flex: 1;
        border-radius: 26px;
        border: 1px solid var(--line);
        background: var(--bg);
        padding: clamp(2rem, 4vw, 3.5rem);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .lg-right-inner {
        max-width: 560px;
        margin: 0 auto;
        width: 100%;
    }
    .lg-right-h2 {
        font-size: clamp(1.9rem, 3vw, 2.55rem);
        font-weight: 750;
        letter-spacing: -0.03em;
        line-height: 1.15;
        color: var(--ink);
        margin-top: 0.45rem;
    }
    .lg-right-h2 span { color: var(--accent); }
    .lg-preview-card {
        margin-top: 2rem;
        border-radius: 20px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: 1.4rem 1.55rem;
        box-shadow: 0 8px 30px -16px rgba(11, 18, 32, 0.08);
    }
    .lg-preview-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px solid var(--line);
        font-size: 12px;
        color: var(--ink-3);
    }
    .lg-bar-track {
        position: relative;
        height: 8px;
        border-radius: 9999px;
        overflow: hidden;
        display: flex;
        gap: 2px;
        margin-top: 0.9rem;
    }
    .lg-metrics {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
        margin-top: 1.25rem;
        padding-top: 1rem;
        border-top: 1px solid var(--line);
    }
    .lg-feat-list {
        margin-top: 1.6rem;
        display: flex;
        flex-direction: column;
        gap: 0.95rem;
    }
    .lg-feat-item {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
    }
    .lg-feat-ic {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: 1px solid var(--line);
        background: var(--surface);
        color: var(--accent);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
</style>

<div class="lg-shell">
    <div class="lg-left">
        <div class="lg-top">
            <a href="{{ route('landing') }}" class="lg-brand">
                <x-brand-logo :size="32" />
                <span>Peringatan Dini</span>
            </a>
            <div style="display:flex;align-items:center;gap:0.6rem;">
                <a href="{{ route('landing') }}" class="text-xs font-medium text-ink-2 hover:underline">&larr; Beranda</a>
                <button type="button" class="btn btn-quiet btn-sm" data-theme-toggle aria-label="Ganti tema" style="min-height:34px;padding:0.3rem 0.55rem;">
                    <x-icon name="sun" class="hidden dark:block" /><x-icon name="moon" class="dark:hidden" />
                </button>
            </div>
        </div>

        <div class="lg-form-wrap">
            <p class="lg-eyebrow">Portal Akademik FTII UHAMKA</p>
            <h1 class="lg-title">Masuk ke akunmu</h1>
            <p class="lg-sub">Gunakan NIM untuk mahasiswa, atau ID pegawai untuk DPA, Validator AI, dan Admin Sekretariat.</p>

            @include('partials.flash')

            <form id="login-form" method="POST" action="{{ route('login.store') }}" style="margin-top:1.5rem;display:flex;flex-direction:column;gap:1.05rem;" novalidate>
                @csrf
                <div>
                    <label for="username" class="label">NIM atau ID pegawai</label>
                    <input id="username" name="username" type="text" inputmode="text" autocomplete="username" required autofocus
                           value="{{ old('username') }}" placeholder="Contoh: 2303045009" class="input mono" aria-describedby="@error('username') username-error @enderror">
                    @error('username')<p id="username-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="label">Kata sandi</label>
                    <div class="lg-pass-wrap">
                        <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Masukkan kata sandi" class="input">
                        <button type="button" class="lg-pass-toggle" id="toggle-pass-btn" aria-label="Tampilkan atau sembunyikan kata sandi">
                            <x-icon name="eye" size="size-4" />
                        </button>
                    </div>
                    @error('password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="lg-submit" style="margin-top:0.25rem;">
                    <span>Masuk Portal</span>
                    <span class="lg-submit-circle" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 14L14 6M8 6h6v6"/></svg>
                    </span>
                </button>
            </form>

            <div class="lg-ticket-box">
                <div style="min-width:0;flex:1 1 170px;">
                    <p class="text-xs font-semibold text-ink">Belum punya akun atau lupa sandi?</p>
                    <p class="text-xs text-ink-3" style="margin-top:2px;">Aktivasi dikirim otomatis ke email aktifmu.</p>
                </div>
                <a href="{{ route('tiket.form') }}" class="btn btn-quiet btn-sm" style="white-space:nowrap;font-size:12px;min-height:34px;padding:0.3rem 0.85rem;">Ajukan E-Ticket &nearr;</a>
            </div>

            <p class="text-xs text-ink-3" style="margin-top:1.1rem;">Sesi otomatis berakhir setelah 30 menit tanpa aktivitas demi keamanan akun.</p>
        </div>

        <div class="lg-foot">
            <span>Cegah Sebelum Terlambat &middot; FTII UHAMKA</span>
            <span>Privasi Zero-PII</span>
        </div>
    </div>

    <div class="lg-right-wrap">
        <div class="lg-right">
            <div class="lg-right-inner">
                <p class="lg-eyebrow">Sistem Peringatan Dini Akademik &middot; FTII UHAMKA</p>
                <h2 class="lg-right-h2">Deteksi dini, <span>lulus tepat waktu.</span></h2>
                <p class="lg-sub" style="margin-top:0.75rem;">Pantau risiko nilai semester lebih awal, hitung target UAS minimal, dan kawal progres 20 SKS Pengayaan serta 4 syarat SKPI dalam satu tempat yang tenang.</p>

                <div class="lg-preview-card">
                    <div class="lg-preview-head">
                        <span class="font-medium text-ink-2">Pratinjau Status Semester Berjalan</span>
                        <span class="mono">peringatandini.online/mahasiswa/ringkasan</span>
                    </div>

                    <div style="display:flex;align-items:baseline;justify-content:space-between;gap:1rem;margin-top:1rem;">
                        <div>
                            <p class="text-xs text-ink-3">Status Risiko Semester Berjalan</p>
                            <p style="font-size:1.55rem;font-weight:750;color:var(--ok);letter-spacing:-0.02em;margin-top:2px;">Risiko rendah</p>
                        </div>
                        <p class="mono" style="font-size:1.75rem;font-weight:750;color:var(--ink);">1,2 <span class="text-sm font-normal text-ink-3">/ 10</span></p>
                    </div>

                    <div class="lg-bar-track" aria-hidden="true">
                        <div style="width:25%;background:#10b981;border-radius:9999px;"></div>
                        <div style="width:25%;background:#f59e0b;border-radius:9999px;"></div>
                        <div style="width:50%;background:#ef4444;border-radius:9999px;"></div>
                        <div style="position:absolute;left:12%;top:-2px;bottom:-2px;width:4px;background:var(--ink);border-radius:2px;"></div>
                    </div>
                    <div class="mono" style="display:flex;justify-content:space-between;font-size:11px;color:var(--ink-3);margin-top:6px;">
                        <span>0</span><span>2,5</span><span>5,0</span><span>10</span>
                    </div>

                    <div class="lg-metrics">
                        <div>
                            <p class="text-xs text-ink-3">Target UAS</p>
                            <p class="mono text-xs font-semibold text-ink" style="margin-top:3px;">C: 56 &middot; B: 68 &middot; A: 80</p>
                        </div>
                        <div style="border-left:1px solid var(--line);padding-left:0.75rem;">
                            <p class="text-xs text-ink-3">MK Pengayaan</p>
                            <p class="mono text-xs font-semibold text-ink" style="margin-top:3px;">20 SKS + 4 SKPI</p>
                        </div>
                        <div style="border-left:1px solid var(--line);padding-left:0.75rem;">
                            <p class="text-xs text-ink-3">Pendampingan</p>
                            <p class="text-xs font-semibold" style="margin-top:3px;color:var(--ok);">Terhubung DPA</p>
                        </div>
                    </div>
                </div>

                <div class="lg-feat-list">
                    <div class="lg-feat-item">
                        <span class="lg-feat-ic"><x-icon name="shield-check" size="size-4" /></span>
                        <div>
                            <p class="text-sm font-semibold text-ink">Privasi terjaga penuh</p>
                            <p class="text-xs text-ink-2" style="margin-top:2px;line-height:1.5;">Identitas mahasiswa disamarkan dengan HMAC-SHA256 saat analisis berlangsung dan seluruh riwayat akses tersimpan rapi.</p>
                        </div>
                    </div>
                    <div class="lg-feat-item">
                        <span class="lg-feat-ic"><x-icon name="chart-line-up" size="size-4" /></span>
                        <div>
                            <p class="text-sm font-semibold text-ink">Deteksi dini berbasis data</p>
                            <p class="text-xs text-ink-2" style="margin-top:2px;line-height:1.5;">Menghitung kehadiran, nilai berjalan, dan progres MK Pengayaan beserta saran langkah perbaikan sebelum UAS.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('toggle-pass-btn');
    const inp = document.getElementById('password');
    if (btn && inp) {
        btn.addEventListener('click', function () {
            inp.type = inp.type === 'password' ? 'text' : 'password';
        });
    }
});
</script>
@endsection
