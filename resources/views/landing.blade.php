@extends('layouts.base')

@section('title', 'Tahu risikomu lebih awal')

@section('body')
<style>
    .px-wrap { width: 100%; max-width: 1180px; margin: 0 auto; padding: 0 1.25rem; }
    @media (min-width: 640px) { .px-wrap { padding: 0 2rem; } }

    .px-nav {
        position: sticky; top: 0; z-index: 30;
        background: var(--bg);
        border-bottom: 1px solid var(--line);
    }
    .px-nav-in { height: 64px; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .px-brand { display: flex; align-items: center; gap: 0.65rem; text-decoration: none; color: var(--ink); font-weight: 600; letter-spacing: -0.02em; }
    .px-links { display: none; align-items: center; gap: 1.5rem; }
    .px-links a { font-size: 0.88rem; color: var(--ink-2); text-decoration: none; transition: color 140ms ease; }
    .px-links a:hover { color: var(--ink); }
    @media (min-width: 768px) { .px-links { display: flex; } }

    .px-hero { padding-top: 2.25rem; padding-bottom: 2.5rem; }
    @media (min-width: 768px) { .px-hero { padding-top: 4rem; padding-bottom: 3rem; } }
    .px-win-scale { display: none; }
    @media (min-width: 640px) { .px-win-scale { display: inline; } }
    .px-ticket-btn { display: none; }
    @media (min-width: 640px) { .px-ticket-btn { display: inline-flex; } }
    .px-h1 {
        font-size: clamp(2.2rem, 5vw, 3.6rem);
        font-weight: 600;
        line-height: 1.08;
        letter-spacing: -0.035em;
        color: var(--ink);
        max-width: 18ch;
    }
    .px-sub {
        margin-top: 1rem;
        font-size: 1rem;
        line-height: 1.6;
        color: var(--ink-2);
        max-width: 48ch;
    }

    .px-window {
        margin-top: 2.5rem;
        background: var(--surface);
        border: 1px solid var(--line-strong);
        border-radius: 14px;
        overflow: hidden;
    }
    .px-winbar {
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        padding: 0.7rem 1rem;
        background: var(--sunken);
        border-bottom: 1px solid var(--line);
    }
    .px-dots { display: flex; gap: 6px; }
    .px-dots span { width: 10px; height: 10px; border-radius: 999px; background: var(--line-strong); }
    .px-url {
        font-family: var(--font-mono); font-size: 0.75rem; color: var(--ink-3);
        background: var(--surface); border: 1px solid var(--line); border-radius: 6px;
        padding: 0.2rem 0.75rem; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }

    .px-bento {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
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
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 1.5rem;
    }
    .px-tile:hover { border-color: var(--line-strong); }

    .px-tabs {
        display: inline-flex; padding: 3px; border-radius: 10px;
        background: var(--sunken); border: 1px solid var(--line);
        gap: 2px;
    }
    .px-tab {
        padding: 0.42rem 0.9rem; border-radius: 7px; font-size: 0.84rem; font-weight: 500;
        color: var(--ink-2); background: transparent; border: 0; cursor: pointer;
    }
    .px-tab.is-active {
        background: var(--surface); color: var(--ink);
    }

    .px-pipe {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;
        margin-top: 1rem;
        font-family: var(--font-mono); font-size: 0.78rem;
    }
    .px-node {
        padding: 0.38rem 0.65rem; border-radius: 7px;
        background: var(--sunken); border: 1px solid var(--line-strong); color: var(--ink);
    }
    .px-node-accent {
        border-color: color-mix(in oklab, var(--accent) 45%, var(--line));
        color: var(--accent);
    }
</style>

<header class="px-nav">
    <div class="px-wrap px-nav-in">
        <a href="{{ route('landing') }}" class="px-brand">
            <x-brand-logo :size="28" />
            <span style="font-size:0.95rem;white-space:nowrap;">Cegah Sebelum Terlambat</span>
        </a>

        <div style="display:flex;align-items:center;gap:0.75rem;">
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

<main>
    <section class="px-wrap px-hero">
        <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:2rem;">
            <div>
                <p class="mono js-hero-item" style="font-size:0.78rem;color:var(--accent);letter-spacing:0.06em;text-transform:uppercase;">
                    FTII UHAMKA • Peringatan Dini Akademik
                </p>
                <h1 class="px-h1 js-hero-item" style="margin-top:0.75rem;">
                    Ketahui risiko nilaimu lebih awal.
                </h1>
                <p class="px-sub js-hero-item">
                    Pantau proyeksi nilai, target UAS minimal, progres 20 SKS pengayaan &amp; wajib PKM, serta saran belajar berbasis AI.
                </p>
            </div>
            <div class="js-hero-item" style="display:flex;flex-wrap:wrap;gap:0.75rem;">
                @auth
                    <a href="{{ route('beranda') }}" class="btn btn-primary h-11 px-5">Buka Beranda</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary h-11 px-5">Masuk</a>
                    <a href="{{ route('tiket.form') }}" class="btn btn-quiet h-11 px-5">Ajukan Akun</a>
                @endauth
            </div>
        </div>

        <div class="px-window js-hero-window">
            <div class="px-winbar">
                <div class="px-dots"><span></span><span></span><span></span></div>
                <span class="px-url">peringatandini.online/mahasiswa/ringkasan</span>
                <span class="mono px-win-scale" style="font-size:0.72rem;color:var(--ink-3);white-space:nowrap;">Skala UHAMKA</span>
            </div>
            <div style="padding:1.75rem;">
                <x-risk-panel :result="$sample" version="gemini-uhamka" compact />
            </div>
        </div>
    </section>

    <section id="bento" class="px-wrap" style="padding-bottom:4rem;">
        <div class="js-section-head" style="display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:1rem;">
            <h2 style="font-size:1.55rem;font-weight:600;letter-spacing:-0.025em;">Transparan, aman, dan sesuai aturan FTII</h2>
            <a href="{{ route('metodologi') }}" class="mono" style="font-size:0.82rem;color:var(--accent);text-decoration:none;">Lihat rumus →</a>
        </div>

        <div class="px-bento">
            <div class="px-tile px-col-7 js-bento-tile">
                <p class="mono" style="font-size:0.72rem;color:var(--ink-3);text-transform:uppercase;letter-spacing:0.06em;">Privacy-by-Design</p>
                <h3 style="margin-top:0.45rem;font-size:1.15rem;font-weight:600;">Zero-PII ke layanan AI</h3>
                <p style="margin-top:0.4rem;font-size:0.9rem;color:var(--ink-2);line-height:1.55;">
                    NIM disamarkan dengan HMAC-SHA256. AI hanya memproses angka statistik anonim.
                </p>
                <div class="px-pipe">
                    <span class="px-node">NIM</span>
                    <span style="color:var(--ink-3);">→</span>
                    <span class="px-node px-node-accent">HMAC-SHA256</span>
                    <span style="color:var(--ink-3);">→</span>
                    <span class="px-node">10 Variabel</span>
                    <span style="color:var(--ink-3);">→</span>
                    <span class="px-node px-node-accent">Gemini + Guardrail</span>
                </div>
            </div>

            <div class="px-tile px-col-5 js-bento-tile">
                <p class="mono" style="font-size:0.72rem;color:var(--ink-3);text-transform:uppercase;letter-spacing:0.06em;">Aturan UHAMKA</p>
                <h3 style="margin-top:0.45rem;font-size:1.15rem;font-weight:600;">Skala Nilai &amp; Hadir 75%</h3>
                <p style="margin-top:0.4rem;font-size:0.9rem;color:var(--ink-2);line-height:1.55;">
                    UAS 40% · UTS 25% · Tugas 25% · Hadir 10%. Hadir &lt; 75% otomatis waspada.
                </p>
                <div style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:0.5rem;" class="mono">
                    <span class="px-node">A ≥ 80</span>
                    <span class="px-node">B ≥ 68</span>
                    <span class="px-node">C ≥ 56</span>
                    <span class="px-node">D ≥ 45</span>
                </div>
            </div>

            <div class="px-tile px-col-4 js-bento-tile">
                <p class="mono" style="font-size:0.72rem;color:var(--ink-3);text-transform:uppercase;letter-spacing:0.06em;">Cakupan</p>
                <h3 style="margin-top:0.45rem;font-size:1.1rem;font-weight:600;">5 Program Studi FTII</h3>
                <ul style="margin-top:0.75rem;font-size:0.86rem;color:var(--ink-2);list-style:none;padding:0;">
                    <li style="padding:0.25rem 0;border-bottom:1px solid var(--line);">Teknik Informatika</li>
                    <li style="padding:0.25rem 0;border-bottom:1px solid var(--line);">Sistem &amp; Teknologi Informasi</li>
                    <li style="padding:0.25rem 0;border-bottom:1px solid var(--line);">Teknik Elektro</li>
                    <li style="padding:0.25rem 0;border-bottom:1px solid var(--line);">Teknik Mesin</li>
                    <li style="padding:0.25rem 0;">Teknologi Rekayasa Mekatronika</li>
                </ul>
            </div>

            <div id="peran" class="px-tile px-col-8 js-bento-tile">
                <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
                    <div>
                        <p class="mono" style="font-size:0.72rem;color:var(--ink-3);text-transform:uppercase;letter-spacing:0.06em;">Alur Pengguna</p>
                        <h3 style="margin-top:0.3rem;font-size:1.15rem;font-weight:600;">Peran dalam sistem</h3>
                    </div>
                    <div class="px-tabs" role="tablist" aria-label="Pilih peran pengguna">
                        <button type="button" class="px-tab is-active" data-role-tab="mhs">Mahasiswa</button>
                        <button type="button" class="px-tab" data-role-tab="dpa">DPA</button>
                        <button type="button" class="px-tab" data-role-tab="admin">Sekretariat</button>
                    </div>
                </div>

                <div id="role-panel" style="margin-top:1.25rem;padding-top:1.1rem;border-top:1px solid var(--line);">
                    <p id="role-title" style="font-weight:600;font-size:1rem;color:var(--ink);"></p>
                    <p id="role-desc" style="margin-top:0.35rem;font-size:0.88rem;color:var(--ink-2);line-height:1.55;"></p>
                    <div id="role-bullets" style="margin-top:0.9rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:0.65rem;"></div>
                </div>
            </div>
        </div>
    </section>
</main>

<footer style="border-top:1px solid var(--line);padding:1.75rem 0;font-size:0.82rem;color:var(--ink-3);">
    <div class="px-wrap" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
        <p>Cegah Sebelum Terlambat • FTII UHAMKA</p>
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
            title: 'Pantau nilai berjalan & target 20 SKS pengayaan',
            desc: 'Impor KHS (CSV/XLSX/PDF) atau isi nilai langsung untuk melihat target UAS minimal dan status wajib PKM.',
            items: ['Rincian Nilai & Proyeksi N', 'Target UAS Minimal (U_min)', 'Pelacak 20 SKS & Wajib PKM']
        },
        dpa: {
            title: 'Deteksi dini bimbingan & catat tindak lanjut',
            desc: 'Pantau mahasiswa yang memberi izin, terima notifikasi risiko tinggi, dan dokumentasikan pertemuan bimbingan.',
            items: ['Ringkasan Kohor & Tren', 'Progres 20 SKS Pengayaan', 'Catatan & Status Bimbingan']
        },
        admin: {
            title: 'Kelola akun, E-Ticket, model, dan audit akses',
            desc: 'Verifikasi pengajuan akun, latih model ML pada data historis anonim, dan pantau log akses NIM.',
            items: ['Persetujuan E-Ticket + Email', 'Manajemen Akun & DPA', 'Evaluasi Model & Log Akses']
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
            '<div class="js-role-pill" style="padding:0.55rem 0.75rem;border-radius:8px;background:var(--sunken);border:1px solid var(--line);font-size:0.82rem;color:var(--ink);">' + item + '</div>'
        ).join('');

        if (animate && !reduceMotion && typeof window.gsap !== 'undefined') {
            window.gsap.fromTo([title, desc, '.js-role-pill'], { y: 5, opacity: 0 }, { y: 0, opacity: 1, duration: 0.24, stagger: 0.03, ease: 'power2.out', force3D: true });
        }
    }

    tabs.forEach(t => t.addEventListener('click', () => showRole(t.dataset.roleTab, true)));
    showRole('mhs', false);

    if (reduceMotion || typeof window.gsap === 'undefined') return;
    const gsap = window.gsap;
    const hasST = typeof window.ScrollTrigger !== 'undefined';
    if (hasST) gsap.registerPlugin(window.ScrollTrigger);

    // 1. Hero entrance singkat & GPU-only
    gsap.from('.js-hero-item, .js-hero-window', {
        y: 14,
        opacity: 0,
        duration: 0.45,
        stagger: 0.06,
        ease: 'power2.out',
        force3D: true,
        clearProps: 'transform,opacity'
    });

    if (!hasST) return;

    // 2. Satu kali reveal ringan untuk bento grid (tanpa continuous scrub agar scroll 100% enteng)
    gsap.from('.js-bento-tile', {
        y: 16,
        opacity: 0,
        duration: 0.42,
        stagger: 0.06,
        ease: 'power2.out',
        force3D: true,
        clearProps: 'transform,opacity',
        scrollTrigger: {
            trigger: '.px-bento',
            start: 'top 90%',
            once: true,
            fastScrollEnd: true
        }
    });
});
</script>
@endsection