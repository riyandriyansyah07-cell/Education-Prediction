@extends('layouts.base')

@section('title', 'Tahu risikomu lebih awal')

@section('body')
<style>
    .px-wrap { width: 100%; max-width: 1180px; margin: 0 auto; padding: 0 1.25rem; }
    @media (min-width: 640px) { .px-wrap { padding: 0 2rem; } }

    .px-nav {
        position: sticky; top: 0; z-index: 30;
        background: color-mix(in oklab, var(--bg) 82%, transparent);
        backdrop-filter: blur(14px);
        border-bottom: 1px solid var(--line);
    }
    .px-nav-in { height: 64px; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .px-brand { display: flex; align-items: center; gap: 0.65rem; text-decoration: none; color: var(--ink); font-weight: 600; letter-spacing: -0.02em; }
    .px-links { display: none; align-items: center; gap: 1.5rem; }
    .px-links a { font-size: 0.88rem; color: var(--ink-2); text-decoration: none; transition: color 140ms ease; }
    .px-links a:hover { color: var(--ink); }
    @media (min-width: 768px) { .px-links { display: flex; } }

    .px-hero { padding-top: 2.25rem; padding-bottom: 2.5rem; }
    @media (min-width: 768px) { .px-hero { padding-top: 4.5rem; padding-bottom: 3.5rem; } }
    .px-win-scale { display: none; }
    @media (min-width: 640px) { .px-win-scale { display: inline; } }
    .px-ticket-btn { display: none; }
    @media (min-width: 640px) { .px-ticket-btn { display: inline-flex; } }
    .px-h1 {
        font-size: clamp(2.25rem, 5vw, 3.75rem);
        font-weight: 600;
        line-height: 1.06;
        letter-spacing: -0.035em;
        color: var(--ink);
        max-width: 18ch;
    }
    .px-sub {
        margin-top: 1.25rem;
        font-size: 1.05rem;
        line-height: 1.65;
        color: var(--ink-2);
        max-width: 52ch;
    }

    /* Window Frame for Product Preview */
    .px-window {
        margin-top: 3rem;
        background: var(--surface);
        border: 1px solid var(--line-strong);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 24px 60px -18px rgba(0, 0, 0, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.05);
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

    /* Asymmetric Bento Grid */
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
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
        transition: border-color 180ms ease, transform 200ms cubic-bezier(0.16, 1, 0.3, 1);
    }
    .px-tile:hover {
        border-color: var(--line-strong);
        transform: translateY(-2px);
    }

    /* Interactive Role Switcher */
    .px-tabs {
        display: inline-flex; padding: 3px; border-radius: 10px;
        background: var(--sunken); border: 1px solid var(--line);
        gap: 2px;
    }
    .px-tab {
        padding: 0.42rem 0.9rem; border-radius: 7px; font-size: 0.84rem; font-weight: 500;
        color: var(--ink-2); background: transparent; border: 0; cursor: pointer;
        transition: background-color 150ms ease, color 150ms ease;
    }
    .px-tab.is-active {
        background: var(--surface); color: var(--ink);
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.25);
    }

    /* Privacy Pipeline Visual */
    .px-pipe {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;
        margin-top: 1.25rem;
        font-family: var(--font-mono); font-size: 0.78rem;
    }
    .px-node {
        padding: 0.4rem 0.65rem; border-radius: 7px;
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
            <svg viewBox="0 0 32 32" width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="2" y="2" width="28" height="28" rx="8" fill="var(--surface)" stroke="var(--line-strong)" stroke-width="1.5"/>
                <path d="M9 20L13.5 15.5L17 18L23 11.5" stroke="var(--accent)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="23" cy="11.5" r="2" fill="var(--ok)"/>
            </svg>
            <span style="font-size:0.95rem;white-space:nowrap;">Cegah Sebelum Terlambat</span>
        </a>

        <div style="display:flex;align-items:center;gap:0.75rem;">
            <nav class="px-links" aria-label="Navigasi utama">
                <a href="#bento">Arsitektur</a>
                <a href="#peran">Alur Peran</a>
                <a href="{{ route('metodologi') }}">Metodologi</a>
            </nav>
            <button type="button" class="btn btn-quiet btn-sm" data-theme-toggle aria-label="Ganti tema terang atau gelap">
                <x-icon name="sun" class="hidden dark:block" /><x-icon name="moon" class="dark:hidden" />
            </button>
            @auth
                <a href="{{ route('beranda') }}" class="btn btn-primary btn-sm">Buka Beranda</a>
            @else
                <a href="{{ route('tiket.form') }}" class="btn btn-quiet btn-sm px-ticket-btn">Ajukan E-Ticket</a>
                <a href="{{ route('login') }}" class="btn btn-primary btn-sm">Masuk</a>
            @endauth
        </div>
    </div>
</header>

<main>
    {{-- Asymmetric Editorial Hero + Framed Product Window --}}
    <section class="px-wrap px-hero">
        <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:2rem;">
            <div>
                <p class="mono" style="font-size:0.78rem;color:var(--accent);letter-spacing:0.06em;text-transform:uppercase;">
                    FTII UHAMKA • Sistem Peringatan Dini Akademik
                </p>
                <h1 class="rise px-h1" style="margin-top:0.85rem;">
                    Ketahui risiko nilaimu sebelum semester berakhir.
                </h1>
                <p class="rise px-sub" style="--i: 1">
                    Hitung proyeksi nilai sementara (N), target UAS minimal (U<sub>min</sub>), dan dapatkan arahan perbaikan berbasis AI yang dikunci aturan akademik UHAMKA.
                </p>
            </div>
            <div class="rise" style="--i: 2;display:flex;flex-wrap:wrap;gap:0.75rem;">
                @auth
                    <a href="{{ route('beranda') }}" class="btn btn-primary h-11 px-5">Buka Beranda</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary h-11 px-5">Masuk ke Aplikasi</a>
                    <a href="{{ route('tiket.form') }}" class="btn btn-quiet h-11 px-5">Ajukan Akun (E-Ticket)</a>
                @endauth
            </div>
        </div>

        {{-- Product Window Preview --}}
        <div class="rise px-window" style="--i: 3">
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

    {{-- Bento Architecture Section --}}
    <section id="bento" class="px-wrap" style="padding-bottom:4.5rem;">
        <div style="display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:1rem;">
            <h2 style="font-size:1.65rem;font-weight:600;letter-spacing:-0.025em;">Dirancang untuk transparansi &amp; akurasi akademik</h2>
            <a href="{{ route('metodologi') }}" class="mono" style="font-size:0.82rem;color:var(--accent);text-decoration:none;">Lihat rumus lengkap →</a>
        </div>

        <div class="px-bento">
            {{-- Bento 1: Privacy Pipeline --}}
            <div class="px-tile px-col-7">
                <p class="mono" style="font-size:0.72rem;color:var(--ink-3);text-transform:uppercase;letter-spacing:0.06em;">Privacy-by-Design</p>
                <h3 style="margin-top:0.5rem;font-size:1.2rem;font-weight:600;">Identitas NIM tidak pernah dikirim ke AI</h3>
                <p style="margin-top:0.5rem;font-size:0.92rem;color:var(--ink-2);line-height:1.6;">
                    Sebelum analisis dijalankan, NIM dipseudonimisasi dengan HMAC-SHA256. Gemini hanya menerima vektor angka anonim untuk menyusun rekomendasi belajar.
                </p>
                <div class="px-pipe">
                    <span class="px-node">NIM 2303045009</span>
                    <span style="color:var(--ink-3);">→</span>
                    <span class="px-node px-node-accent">HMAC-SHA256</span>
                    <span style="color:var(--ink-3);">→</span>
                    <span class="px-node">10 Variabel Numerik</span>
                    <span style="color:var(--ink-3);">→</span>
                    <span class="px-node px-node-accent">Gemini + Guardrail</span>
                </div>
            </div>

            {{-- Bento 2: UHAMKA Guardrail --}}
            <div class="px-tile px-col-5">
                <p class="mono" style="font-size:0.72rem;color:var(--ink-3);text-transform:uppercase;letter-spacing:0.06em;">Aturan Baku Kampus</p>
                <h3 style="margin-top:0.5rem;font-size:1.2rem;font-weight:600;">Skala Nilai &amp; Batas Hadir 75%</h3>
                <p style="margin-top:0.5rem;font-size:0.92rem;color:var(--ink-2);line-height:1.6;">
                    Kehadiran di bawah 75% otomatis memicu risiko tinggi. Bobot komponen: UAS 40%, UTS 25%, Tugas 25%, Hadir 10%.
                </p>
                <div style="margin-top:1.1rem;display:flex;flex-wrap:wrap;gap:0.5rem;" class="mono">
                    <span class="px-node">A ≥ 80</span>
                    <span class="px-node">B ≥ 68</span>
                    <span class="px-node">C ≥ 56</span>
                    <span class="px-node">D ≥ 45</span>
                </div>
            </div>

            {{-- Bento 3: Program Studi --}}
            <div class="px-tile px-col-4">
                <p class="mono" style="font-size:0.72rem;color:var(--ink-3);text-transform:uppercase;letter-spacing:0.06em;">Cakupan Fakultas</p>
                <h3 style="margin-top:0.5rem;font-size:1.15rem;font-weight:600;">5 Program Studi FTII</h3>
                <ul style="margin-top:0.85rem;space-y:0.4rem;font-size:0.88rem;color:var(--ink-2);list-style:none;padding:0;">
                    <li style="padding:0.25rem 0;border-bottom:1px solid var(--line);">Teknik Informatika</li>
                    <li style="padding:0.25rem 0;border-bottom:1px solid var(--line);">Sistem &amp; Teknologi Informasi</li>
                    <li style="padding:0.25rem 0;border-bottom:1px solid var(--line);">Teknik Elektro</li>
                    <li style="padding:0.25rem 0;border-bottom:1px solid var(--line);">Teknik Mesin</li>
                    <li style="padding:0.25rem 0;">Teknologi Rekayasa Mekatronika</li>
                </ul>
            </div>

            {{-- Bento 4: Interactive Role Switcher --}}
            <div id="peran" class="px-tile px-col-8">
                <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
                    <div>
                        <p class="mono" style="font-size:0.72rem;color:var(--ink-3);text-transform:uppercase;letter-spacing:0.06em;">Alur Terintegrasi</p>
                        <h3 style="margin-top:0.35rem;font-size:1.2rem;font-weight:600;">Lihat peran di dalam sistem</h3>
                    </div>
                    <div class="px-tabs" role="tablist" aria-label="Pilih peran pengguna">
                        <button type="button" class="px-tab is-active" data-role-tab="mhs">Mahasiswa</button>
                        <button type="button" class="px-tab" data-role-tab="dpa">DPA</button>
                        <button type="button" class="px-tab" data-role-tab="admin">Sekretariat</button>
                    </div>
                </div>

                <div id="role-panel" style="margin-top:1.35rem;padding-top:1.25rem;border-top:1px solid var(--line);">
                    <p id="role-title" style="font-weight:600;font-size:1.05rem;color:var(--ink);"></p>
                    <p id="role-desc" style="margin-top:0.45rem;font-size:0.92rem;color:var(--ink-2);line-height:1.6;"></p>
                    <div id="role-bullets" style="margin-top:1rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:0.75rem;"></div>
                </div>
            </div>
        </div>
    </section>
</main>

<footer style="border-top:1px solid var(--line);padding:2rem 0;font-size:0.85rem;color:var(--ink-3);">
    <div class="px-wrap" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
        <p>Cegah Sebelum Terlambat • Capstone Project FTII UHAMKA</p>
        <div style="display:flex;gap:1.5rem;">
            <a href="{{ route('metodologi') }}" style="color:var(--ink-2);text-decoration:none;">Metodologi</a>
            <a href="{{ route('tiket.form') }}" style="color:var(--ink-2);text-decoration:none;">E-Ticket Akun</a>
            <a href="{{ route('login') }}" style="color:var(--ink-2);text-decoration:none;">Masuk</a>
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const roles = {
        mhs: {
            title: 'Pantau nilai berjalan & kejar target UAS minimal',
            desc: 'Mahasiswa mengimpor KHS (CSV/Excel/PDF) atau mengisi langsung nilai UTS, tugas, dan kehadiran untuk mengetahui posisi nilai sebelum UAS.',
            items: ['Input Cepat & Cek Berkala', 'Target UAS Minimal (U_min)', 'Pengingat Email Otomatis']
        },
        dpa: {
            title: 'Deteksi dini mahasiswa bimbingan & catat tindak lanjut',
            desc: 'Dosen Pembimbing Akademik menerima notifikasi eskalasi risiko tinggi, menilai keaktifan bimbingan, dan mendokumentasikan catatan pertemuan.',
            items: ['Ringkasan Kohor Mahasiswa', 'Penilaian Keaktifan & TA', 'Log Tindak Lanjut Bimbingan']
        },
        admin: {
            title: 'Verifikasi E-Ticket & audit akses terpusat',
            desc: 'Sekretariat memverifikasi pengajuan akun mahasiswa/DPA via E-Ticket (dengan notifikasi email otomatis), menetapkan DPA, dan memantau log akses.',
            items: ['Persetujuan E-Ticket + Email', 'Penetapan DPA Mahasiswa', 'Audit Catatan Akses NIM']
        }
    };

    const tabs = document.querySelectorAll('[data-role-tab]');
    const title = document.getElementById('role-title');
    const desc = document.getElementById('role-desc');
    const bullets = document.getElementById('role-bullets');

    function showRole(key) {
        const r = roles[key];
        if (!r) return;
        tabs.forEach(t => t.classList.toggle('is-active', t.dataset.roleTab === key));
        title.textContent = r.title;
        desc.textContent = r.desc;
        bullets.innerHTML = r.items.map(item =>
            '<div style="padding:0.6rem 0.8rem;border-radius:8px;background:var(--sunken);border:1px solid var(--line);font-size:0.84rem;color:var(--ink);">' + item + '</div>'
        ).join('');
    }

    tabs.forEach(t => t.addEventListener('click', () => showRole(t.dataset.roleTab)));
    showRole('mhs');
});
</script>
@endsection