@extends('layouts.base')

@section('title', 'Tahu risikomu lebih awal')

@section('body')
<style>
    .px-wrap { width: 100%; max-width: 1180px; margin: 0 auto; padding: 0 1.25rem; }
    @media (min-width: 640px) { .px-wrap { padding: 0 2rem; } }

    /* Ambient Architectural Backdrop (Subtle, Editorial, Anti-Slop) */
    .px-stage {
        position: relative;
        overflow-x: clip;
    }
    .px-stage::before {
        content: "";
        position: absolute;
        inset: -120px 0 auto 0;
        height: 540px;
        pointer-events: none;
        background:
            radial-gradient(60% 55% at 22% 18%, color-mix(in srgb, var(--accent) 16%, transparent) 0%, transparent 72%),
            radial-gradient(50% 45% at 82% 24%, color-mix(in srgb, var(--ok) 12%, transparent) 0%, transparent 70%);
        z-index: 0;
    }
    .px-grid-bg {
        position: absolute;
        inset: 0 0 auto 0;
        height: 560px;
        pointer-events: none;
        background-image:
            linear-gradient(to right, color-mix(in srgb, var(--line) 55%, transparent) 1px, transparent 1px),
            linear-gradient(to bottom, color-mix(in srgb, var(--line) 55%, transparent) 1px, transparent 1px);
        background-size: 48px 48px;
        mask-image: radial-gradient(ellipse 75% 70% at 50% 20%, #000 10%, transparent 80%);
        -webkit-mask-image: radial-gradient(ellipse 75% 70% at 50% 20%, #000 10%, transparent 80%);
        z-index: 0;
    }
    .px-stage > * { position: relative; z-index: 1; }

    .px-nav {
        position: sticky; top: 0; z-index: 30;
        background: color-mix(in srgb, var(--bg) 82%, transparent);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border-bottom: 1px solid var(--line);
    }
    .px-nav-in { height: 64px; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .px-brand { display: flex; align-items: center; gap: 0.65rem; text-decoration: none; color: var(--ink); font-weight: 600; letter-spacing: -0.02em; }
    .px-links { display: none; align-items: center; gap: 1.5rem; }
    .px-links a { font-size: 0.88rem; color: var(--ink-2); text-decoration: none; transition: color 140ms ease; }
    .px-links a:hover { color: var(--ink); }
    @media (min-width: 768px) { .px-links { display: flex; } }

    .px-hero { padding-top: 2.5rem; padding-bottom: 2.75rem; }
    @media (min-width: 768px) { .px-hero { padding-top: 4.25rem; padding-bottom: 3.5rem; } }
    .px-win-scale { display: none; }
    @media (min-width: 640px) { .px-win-scale { display: inline; } }
    .px-ticket-btn { display: none; }
    @media (min-width: 640px) { .px-ticket-btn { display: inline-flex; } }

    /* Live Eyebrow Pill */
    .px-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.32rem 0.85rem;
        border-radius: 9999px;
        border: 1px solid color-mix(in srgb, var(--accent) 32%, var(--line));
        background: color-mix(in srgb, var(--accent) 8%, var(--surface));
        font-size: 0.74rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--ink);
    }
    .px-pulse-dot {
        width: 8px; height: 8px; border-radius: 9999px; background: var(--ok);
        position: relative; flex-shrink: 0;
    }
    .px-pulse-dot::after {
        content: "";
        position: absolute;
        inset: -4px;
        border-radius: 9999px;
        border: 2px solid var(--ok);
        opacity: 0;
        animation: pxPulse 2.2s cubic-bezier(0.22, 1, 0.36, 1) infinite;
    }
    @keyframes pxPulse {
        0% { transform: scale(0.7); opacity: 0.75; }
        100% { transform: scale(1.9); opacity: 0; }
    }

    /* Kinetic Highlighted Headline */
    .px-h1 {
        font-size: clamp(2.15rem, 5.2vw, 3.75rem);
        font-weight: 700;
        line-height: 1.08;
        letter-spacing: -0.038em;
        color: var(--ink);
        max-width: 17ch;
    }
    .px-h1-accent {
        position: relative;
        display: inline-block;
        background: linear-gradient(115deg, var(--accent) 0%, #60a5fa 45%, var(--ok) 100%);
        background-size: 200% auto;
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        animation: pxShimmer 7s ease-in-out infinite;
    }
    @keyframes pxShimmer {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    .px-h1-underline {
        position: absolute;
        left: 0;
        right: 0;
        bottom: -7px;
        width: 100%;
        height: 12px;
        overflow: visible;
        pointer-events: none;
    }
    .px-h1-underline path {
        stroke-dasharray: 280;
        stroke-dashoffset: 280;
    }

    /* Inline Tactile Highlights in Subheadline */
    .px-sub {
        margin-top: 1.25rem;
        font-size: 1.02rem;
        line-height: 1.72;
        color: var(--ink-2);
        max-width: 52ch;
    }
    .px-hl {
        color: var(--ink);
        font-weight: 600;
        padding: 0.08rem 0.42rem;
        border-radius: 6px;
        background: color-mix(in srgb, var(--accent) 12%, var(--surface));
        border: 1px solid color-mix(in srgb, var(--accent) 24%, var(--line));
        white-space: nowrap;
    }
    .px-hl-ok {
        color: var(--ok);
        font-weight: 600;
        padding: 0.08rem 0.45rem;
        border-radius: 6px;
        background: color-mix(in srgb, var(--ok) 12%, var(--surface));
        border: 1px solid color-mix(in srgb, var(--ok) 28%, var(--line));
        white-space: nowrap;
    }

    /* Section Highlight */
    .px-sec-hl {
        background: linear-gradient(120deg, color-mix(in srgb, var(--accent) 18%, transparent) 0%, color-mix(in srgb, var(--ok) 16%, transparent) 100%);
        padding: 0.05rem 0.45rem;
        border-radius: 7px;
        color: var(--ink);
    }

    /* 3D Perspective Preview Window */
    .px-window-wrap {
        perspective: 1200px;
        margin-top: 2.75rem;
    }
    .px-window {
        background: var(--surface);
        border: 1px solid var(--line-strong);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 24px 60px -24px rgba(2, 6, 23, 0.32), 0 0 0 1px color-mix(in srgb, var(--accent) 10%, transparent);
        transform-style: preserve-3d;
        transition: border-color 220ms ease, box-shadow 220ms ease;
    }
    .px-window:hover {
        border-color: color-mix(in srgb, var(--accent) 45%, var(--line-strong));
    }
    .px-winbar {
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        padding: 0.72rem 1rem;
        background: var(--sunken);
        border-bottom: 1px solid var(--line);
    }
    .px-dots { display: flex; gap: 6px; }
    .px-dots span { width: 10px; height: 10px; border-radius: 999px; background: var(--line-strong); }
    .px-dots span:nth-child(1) { background: color-mix(in srgb, var(--bad) 70%, var(--line-strong)); }
    .px-dots span:nth-child(2) { background: color-mix(in srgb, var(--warn) 75%, var(--line-strong)); }
    .px-dots span:nth-child(3) { background: color-mix(in srgb, var(--ok) 75%, var(--line-strong)); }
    .px-url {
        font-family: var(--font-mono); font-size: 0.75rem; color: var(--ink-3);
        background: var(--surface); border: 1px solid var(--line); border-radius: 7px;
        padding: 0.22rem 0.75rem; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }

    /* Bento Grid with Cursor-Tracking Spotlight */
    .px-bento {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.1rem;
        margin-top: 1.5rem;
    }
    @media (min-width: 768px) {
        .px-bento { grid-template-columns: repeat(12, minmax(0, 1fr)); }
        .px-col-7 { grid-column: span 7; }
        .px-col-5 { grid-column: span 5; }
        .px-col-4 { grid-column: span 4; }
        .px-col-8 { grid-column: span 8; }
    }
    .px-tile {
        position: relative;
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 16px;
        padding: 1.55rem;
        overflow: hidden;
        transition: transform 220ms cubic-bezier(0.22, 1, 0.36, 1), border-color 220ms ease, box-shadow 220ms ease;
    }
    .px-tile::before {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0;
        background: radial-gradient(360px circle at var(--mx, 50%) var(--my, 50%), color-mix(in srgb, var(--accent) 14%, transparent), transparent 65%);
        transition: opacity 220ms ease;
    }
    .px-tile:hover {
        transform: translateY(-3px);
        border-color: color-mix(in srgb, var(--accent) 42%, var(--line-strong));
        box-shadow: 0 18px 40px -24px rgba(2, 6, 23, 0.28);
    }
    .px-tile:hover::before { opacity: 1; }
    .px-tile > * { position: relative; z-index: 1; }

    /* Interactive Tabs */
    .px-tabs {
        display: inline-flex; flex-wrap: wrap; padding: 3px; border-radius: 10px;
        background: var(--sunken); border: 1px solid var(--line);
        gap: 2px;
    }
    .px-tab {
        padding: 0.42rem 0.85rem; border-radius: 7px; font-size: 0.82rem; font-weight: 500;
        color: var(--ink-2); background: transparent; border: 0; cursor: pointer;
        transition: background-color 160ms ease, color 160ms ease, box-shadow 160ms ease;
    }
    .px-tab:hover { color: var(--ink); }
    .px-tab.is-active {
        background: var(--surface); color: var(--ink);
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
    }

    /* Animated Live Data Pipeline */
    .px-pipe {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;
        margin-top: 1.15rem;
        font-family: var(--font-mono); font-size: 0.78rem;
    }
    .px-node {
        padding: 0.4rem 0.7rem; border-radius: 8px;
        background: var(--sunken); border: 1px solid var(--line-strong); color: var(--ink);
        transition: border-color 240ms ease, background-color 240ms ease, transform 240ms ease, box-shadow 240ms ease;
    }
    .px-node-accent {
        border-color: color-mix(in oklab, var(--accent) 45%, var(--line));
        color: var(--accent);
    }
    .px-node.is-lit {
        border-color: var(--accent);
        background: color-mix(in srgb, var(--accent) 14%, var(--surface));
        transform: translateY(-2px);
        box-shadow: 0 6px 16px -8px color-mix(in srgb, var(--accent) 60%, transparent);
    }

    @media (max-width: 640px) {
        .px-tile { padding: 1.25rem; }
        .px-h1 { max-width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) {
        .px-pulse-dot::after, .px-h1-accent { animation: none !important; }
        .px-h1-underline path { stroke-dashoffset: 0 !important; }
    }
</style>

<header class="px-nav">
    <div class="px-wrap px-nav-in">
        <a href="{{ route('landing') }}" class="px-brand">
            <x-brand-logo :size="28" />
            <span style="font-size:0.95rem;white-space:nowrap;">Cegah Sebelum Terlambat</span>
        </a>

        <div style="display:flex;align-items:center;gap:0.65rem;">
            <nav class="px-links" aria-label="Navigasi utama">
                <a href="#bento">Fitur</a>
                <a href="#peran">Peran</a>
                <a href="{{ route('metodologi') }}">Metodologi</a>
            </nav>
            <button type="button" class="btn btn-quiet btn-sm" data-theme-toggle aria-label="Ganti tema terang atau gelap">
                <x-icon name="sun" class="hidden dark:block" /><x-icon name="moon" class="dark:hidden" />
            </button>
            @auth
                <a href="{{ route('beranda') }}" class="btn btn-primary btn-sm">Beranda</a>
            @else
                <a href="{{ route('tiket.form') }}" class="btn btn-quiet btn-sm px-ticket-btn">E-Ticket</a>
                <a href="{{ route('login') }}" class="btn btn-primary btn-sm">Masuk</a>
            @endauth
        </div>
    </div>
</header>

<main class="px-stage">
    <div class="px-grid-bg" aria-hidden="true"></div>

    <section class="px-wrap px-hero">
        <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:2rem;">
            <div>
                <div class="px-eyebrow mono js-hero-item">
                    <span class="px-pulse-dot" aria-hidden="true"></span>
                    <span>FTII UHAMKA &middot; Peringatan Dini Berbasis Gemini AI</span>
                </div>
                <h1 class="px-h1 js-hero-item" style="margin-top:1.1rem;">
                    Ketahui
                    <span class="px-h1-accent">
                        risiko nilaimu
                        <svg class="px-h1-underline js-hero-beam" viewBox="0 0 260 14" preserveAspectRatio="none" fill="none" aria-hidden="true">
                            <defs>
                                <linearGradient id="pxBeamGrad" x1="0" y1="0" x2="260" y2="0" gradientUnits="userSpaceOnUse">
                                    <stop offset="0%" stop-color="#38bdf8"/>
                                    <stop offset="100%" stop-color="#34d399"/>
                                </linearGradient>
                            </defs>
                            <path d="M3 10.5C68 3.2 184 2.5 257 9.5" stroke="url(#pxBeamGrad)" stroke-width="3.2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    lebih awal.
                </h1>
                <p class="px-sub js-hero-item">
                    Pantau <span class="px-hl">proyeksi nilai</span>, <span class="px-hl">target UAS minimal</span>, progres <span class="px-hl">20 SKS &amp; 4 SKPI</span>, serta saran belajar adaptif bertenaga <span class="px-hl-ok">Gemini AI</span>.
                </p>
            </div>
            <div class="js-hero-item" style="display:flex;flex-wrap:wrap;gap:0.75rem;">
                @auth
                    <a href="{{ route('beranda') }}" class="btn btn-primary" style="min-height:44px;padding:0.6rem 1.3rem;">Buka Beranda &rarr;</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary" style="min-height:44px;padding:0.6rem 1.35rem;">Masuk Sekarang &rarr;</a>
                    <a href="{{ route('tiket.form') }}" class="btn btn-quiet" style="min-height:44px;padding:0.6rem 1.2rem;">Ajukan Akun</a>
                @endauth
            </div>
        </div>

        <div class="px-window-wrap">
            <div class="px-window js-hero-window">
                <div class="px-winbar">
                    <div class="px-dots"><span></span><span></span><span></span></div>
                    <span class="px-url">peringatandini.online/mahasiswa/ringkasan</span>
                    <span class="mono px-win-scale" style="font-size:0.72rem;color:var(--ink-3);white-space:nowrap;">Gemini AI &middot; Skala UHAMKA</span>
                </div>
                <div style="padding:1.5rem 1.75rem;">
                    <x-risk-panel :result="$sample" version="gemini-uhamka" compact />
                </div>
            </div>
        </div>
    </section>

    <section id="bento" class="px-wrap" style="padding-bottom:4.5rem;">
        <div class="js-section-head" style="display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:1rem;">
            <h2 style="font-size:clamp(1.35rem, 2.4vw, 1.65rem);font-weight:650;letter-spacing:-0.025em;">
                Transparan, aman, dan <span class="px-sec-hl">sesuai aturan FTII</span>
            </h2>
            <a href="{{ route('metodologi') }}" class="mono" style="font-size:0.82rem;color:var(--accent);text-decoration:none;">Lihat rumus lengkap &rarr;</a>
        </div>

        <div class="px-bento">
            <div class="px-tile px-col-7 js-bento-tile">
                <p class="mono" style="font-size:0.72rem;color:var(--accent);text-transform:uppercase;letter-spacing:0.06em;font-weight:600;">Privacy-by-Design</p>
                <h3 style="margin-top:0.45rem;font-size:1.15rem;font-weight:600;">Zero-PII ke layanan AI + Guardrail</h3>
                <p style="margin-top:0.4rem;font-size:0.9rem;color:var(--ink-2);line-height:1.6;">
                    NIM disamarkan dengan <strong style="color:var(--ink);">HMAC-SHA256</strong>. Gemini AI hanya memproses angka statistik anonim dan disaring otomatis dari halusinasi.
                </p>
                <div class="px-pipe" id="js-live-pipe">
                    <span class="px-node js-pipe-step">NIM</span>
                    <span style="color:var(--ink-3);">&rarr;</span>
                    <span class="px-node px-node-accent js-pipe-step">HMAC-SHA256</span>
                    <span style="color:var(--ink-3);">&rarr;</span>
                    <span class="px-node js-pipe-step">10 Variabel</span>
                    <span style="color:var(--ink-3);">&rarr;</span>
                    <span class="px-node px-node-accent js-pipe-step">Gemini + Guardrail</span>
                </div>
            </div>

            <div class="px-tile px-col-5 js-bento-tile">
                <p class="mono" style="font-size:0.72rem;color:var(--ok);text-transform:uppercase;letter-spacing:0.06em;font-weight:600;">Aturan UHAMKA</p>
                <h3 style="margin-top:0.45rem;font-size:1.15rem;font-weight:600;">Skala Nilai &amp; Hadir 75%</h3>
                <p style="margin-top:0.4rem;font-size:0.9rem;color:var(--ink-2);line-height:1.6;">
                    UAS 40% &middot; UTS 25% &middot; Tugas 25% &middot; Hadir 10%. Hadir <strong style="color:var(--ink);">&lt; 75%</strong> otomatis menaikkan status waspada.
                </p>
                <div style="margin-top:1.1rem;display:flex;flex-wrap:wrap;gap:0.5rem;" class="mono">
                    <span class="px-node" style="border-color:color-mix(in srgb, var(--ok) 35%, var(--line));">A &ge; 80</span>
                    <span class="px-node">B &ge; 68</span>
                    <span class="px-node">C &ge; 56</span>
                    <span class="px-node" style="border-color:color-mix(in srgb, var(--warn) 35%, var(--line));">D &ge; 45</span>
                </div>
            </div>

            <div class="px-tile px-col-4 js-bento-tile">
                <p class="mono" style="font-size:0.72rem;color:var(--ink-3);text-transform:uppercase;letter-spacing:0.06em;font-weight:600;">Cakupan Fakultas</p>
                <h3 style="margin-top:0.45rem;font-size:1.1rem;font-weight:600;">5 Program Studi FTII</h3>
                <ul style="margin-top:0.8rem;font-size:0.86rem;color:var(--ink-2);list-style:none;padding:0;">
                    <li style="padding:0.32rem 0;border-bottom:1px solid var(--line);">Teknik Informatika</li>
                    <li style="padding:0.32rem 0;border-bottom:1px solid var(--line);">Sistem &amp; Teknologi Informasi</li>
                    <li style="padding:0.32rem 0;border-bottom:1px solid var(--line);">Teknik Elektro</li>
                    <li style="padding:0.32rem 0;border-bottom:1px solid var(--line);">Teknik Mesin</li>
                    <li style="padding:0.32rem 0;">Teknologi Rekayasa Mekatronika</li>
                </ul>
            </div>

            <div id="peran" class="px-tile px-col-8 js-bento-tile">
                <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
                    <div>
                        <p class="mono" style="font-size:0.72rem;color:var(--accent);text-transform:uppercase;letter-spacing:0.06em;font-weight:600;">Alur 4 Peran</p>
                        <h3 style="margin-top:0.3rem;font-size:1.15rem;font-weight:600;">Peran dalam sistem</h3>
                    </div>
                    <div class="px-tabs" role="tablist" aria-label="Pilih peran pengguna">
                        <button type="button" class="px-tab is-active" data-role-tab="mhs">Mahasiswa</button>
                        <button type="button" class="px-tab" data-role-tab="dpa">DPA</button>
                        <button type="button" class="px-tab" data-role-tab="validator">Validator AI</button>
                        <button type="button" class="px-tab" data-role-tab="admin">Sekretariat</button>
                    </div>
                </div>

                <div id="role-panel" style="margin-top:1.25rem;padding-top:1.1rem;border-top:1px solid var(--line);">
                    <p id="role-title" style="font-weight:600;font-size:1rem;color:var(--ink);"></p>
                    <p id="role-desc" style="margin-top:0.35rem;font-size:0.88rem;color:var(--ink-2);line-height:1.6;"></p>
                    <div id="role-bullets" style="margin-top:0.95rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:0.65rem;"></div>
                </div>
            </div>
        </div>
    </section>
</main>

<footer style="border-top:1px solid var(--line);padding:1.75rem 0;font-size:0.82rem;color:var(--ink-3);">
    <div class="px-wrap" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
        <p>Cegah Sebelum Terlambat &bull; FTII UHAMKA</p>
        <div style="display:flex;gap:1.5rem;">
            <a href="{{ route('metodologi') }}" style="color:var(--ink-2);text-decoration:none;">Metodologi</a>
            <a href="{{ route('tiket.form') }}" style="color:var(--ink-2);text-decoration:none;">E-Ticket</a>
            <a href="{{ route('login') }}" style="color:var(--ink-2);text-decoration:none;">Masuk</a>
        </div>
    </div>
</footer>

<script>
document.body.dataset.customMotion = '1';

window.addEventListener('DOMContentLoaded', function () {
    const roles = {
        mhs: {
            title: 'Pantau nilai berjalan, 20 SKS pengayaan & 4 syarat SKPI',
            desc: 'Impor KHS (CSV/XLSX/PDF) atau isi nilai langsung untuk melihat target UAS minimal, progres 20 SKS, dan 4 syarat wajib SKPI (Kompetensi IT, BAPS, PKM min. Universitas, TOEFL UHAMKA).',
            items: ['Rincian Nilai & Proyeksi N', 'Target UAS Minimal (U_min)', 'Pelacak 20 SKS & 4 Syarat SKPI']
        },
        dpa: {
            title: 'Dasbor visual bimbingan, antrean prioritas & catatan perwalian',
            desc: 'Pantau grafik distribusi risiko anak bimbingan yang memberi izin, tindak lanjuti mahasiswa prioritas, dan dokumentasikan bimbingan.',
            items: ['Grafik Risiko & Progres Tugas', 'Antrean Prioritas Segera Dihubungi', 'Checklist SKPI & Catatan Bimbingan']
        },
        validator: {
            title: 'Audit Human-in-the-Loop hasil Gemini AI vs pembanding matematis',
            desc: 'Bandingkan skor dan faktor risiko dari Gemini AI secara berdampingan terhadap rumus RiskModel untuk memastikan akurasi evaluasi.',
            items: ['Bandingkan Gemini vs RiskModel', 'Tandai Sesuai / Perlu Koreksi', 'Ekspor Laporan Audit CSV']
        },
        admin: {
            title: 'Dasbor eksekutif fakultas, E-Ticket, model & kesehatan AI',
            desc: 'Pantau kohort Prodi × Angkatan, verifikasi pengajuan akun, monitor status kesehatan Gemini AI, dan latih model ML anonim.',
            items: ['Dasbor Kohort Prodi & Angkatan', 'Indikator Kesehatan Mesin AI', 'E-Ticket Aman & Log Akses NIM']
        }
    };

    const tabs = document.querySelectorAll('[data-role-tab]');
    const title = document.getElementById('role-title');
    const desc = document.getElementById('role-desc');
    const bullets = document.getElementById('role-bullets');
    const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function showRole(key, animate) {
        const r = roles[key];
        if (!r) return;
        tabs.forEach(t => t.classList.toggle('is-active', t.dataset.roleTab === key));
        title.textContent = r.title;
        desc.textContent = r.desc;
        bullets.innerHTML = r.items.map(item =>
            '<div class="js-role-pill" style="padding:0.58rem 0.8rem;border-radius:9px;background:var(--sunken);border:1px solid var(--line);font-size:0.82rem;color:var(--ink);font-weight:500;">' + item + '</div>'
        ).join('');

        if (animate && !reduceMotion && typeof window.gsap !== 'undefined') {
            window.gsap.fromTo([title, desc, '.js-role-pill'], { y: 8, opacity: 0 }, { y: 0, opacity: 1, duration: 0.28, stagger: 0.04, ease: 'power2.out', force3D: true });
        }
    }

    tabs.forEach(t => t.addEventListener('click', () => showRole(t.dataset.roleTab, true)));
    showRole('mhs', false);

    // Cursor-tracking spotlight on Bento tiles (desktop pointer devices)
    if (!reduceMotion && window.matchMedia('(pointer: fine)').matches) {
        document.querySelectorAll('.px-tile').forEach(function (tile) {
            tile.addEventListener('pointermove', function (e) {
                const rect = tile.getBoundingClientRect();
                tile.style.setProperty('--mx', (e.clientX - rect.left) + 'px');
                tile.style.setProperty('--my', (e.clientY - rect.top) + 'px');
            }, { passive: true });
        });
    }

    // Live sequential pulse on Zero-PII data pipeline
    if (!reduceMotion) {
        const steps = Array.from(document.querySelectorAll('.js-pipe-step'));
        if (steps.length) {
            let activeIdx = 0;
            setInterval(function () {
                steps.forEach((el, i) => el.classList.toggle('is-lit', i === activeIdx));
                activeIdx = (activeIdx + 1) % steps.length;
            }, 950);
        }
    }

    if (reduceMotion || typeof window.gsap === 'undefined') return;
    const gsap = window.gsap;
    const hasST = typeof window.ScrollTrigger !== 'undefined';
    if (hasST) gsap.registerPlugin(window.ScrollTrigger);

    // 1. Staggered kinetic hero entrance + SVG beam draw
    const tl = gsap.timeline({ defaults: { ease: 'power3.out', force3D: true } });
    tl.from('.js-hero-item', {
        y: 22,
        opacity: 0,
        duration: 0.55,
        stagger: 0.08,
        clearProps: 'transform,opacity'
    })
    .to('.js-hero-beam path', {
        strokeDashoffset: 0,
        duration: 0.75,
        ease: 'power2.out'
    }, '-=0.35')
    .from('.js-hero-window', {
        y: 28,
        rotateX: 6,
        scale: 0.975,
        opacity: 0,
        duration: 0.7,
        ease: 'power3.out',
        clearProps: 'transform,opacity'
    }, '-=0.45');

    if (!hasST) return;

    // 2. Scroll-triggered section heading + Bento reveal
    gsap.from('.js-section-head', {
        y: 18,
        opacity: 0,
        duration: 0.48,
        ease: 'power2.out',
        clearProps: 'transform,opacity',
        scrollTrigger: { trigger: '#bento', start: 'top 88%', once: true }
    });

    gsap.from('.js-bento-tile', {
        y: 24,
        scale: 0.985,
        opacity: 0,
        duration: 0.52,
        stagger: 0.08,
        ease: 'power3.out',
        force3D: true,
        clearProps: 'transform,opacity',
        scrollTrigger: {
            trigger: '.px-bento',
            start: 'top 88%',
            once: true,
            fastScrollEnd: true
        }
    });
});
</script>
@endsection