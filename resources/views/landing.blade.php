@extends('layouts.base')

@section('title', 'Peringatan Dini Akademik FTII UHAMKA')

@section('body')
<style>
    .lp-page {
        min-height: 100vh;
        background: var(--bg);
        color: var(--ink);
    }
    .lp-wrap {
        width: 100%;
        max-width: 1120px;
        margin: 0 auto;
        padding: 0 clamp(1.1rem, 3.5vw, 2rem);
    }
    .lp-nav {
        position: sticky;
        top: 0;
        z-index: 30;
        background: color-mix(in srgb, var(--surface) 92%, transparent);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--line);
    }
    .lp-nav-in {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        height: 68px;
    }
    .lp-brand {
        display: inline-flex;
        align-items: center;
        gap: 0.65rem;
        text-decoration: none;
        color: var(--ink);
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: -0.015em;
    }
    .lp-links {
        display: none;
        align-items: center;
        gap: 1.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--ink-2);
    }
    @media (min-width: 820px) {
        .lp-links { display: flex; }
    }
    .lp-links a { text-decoration: none; color: inherit; transition: color 140ms ease; }
    .lp-links a:hover { color: var(--ink); }

    .lp-pill-cta {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.42rem 0.48rem 0.42rem 1.05rem;
        border-radius: 9999px;
        background: var(--ink);
        color: var(--surface);
        font-size: 0.84rem;
        font-weight: 600;
        text-decoration: none;
        transition: opacity 140ms ease;
    }
    .lp-pill-cta:hover { opacity: 0.9; }
    .lp-pill-cta-dot {
        width: 26px;
        height: 26px;
        border-radius: 9999px;
        background: var(--surface);
        color: var(--ink);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Sections */
    .lp-sec {
        padding: clamp(2.5rem, 5vw, 4.5rem) 0;
    }
    .lp-eyebrow {
        font-size: 12px;
        font-weight: 600;
        color: var(--accent);
        margin-bottom: 0.5rem;
    }
    .lp-h1 {
        font-size: clamp(2.1rem, 4.6vw, 3.35rem);
        font-weight: 750;
        letter-spacing: -0.035em;
        line-height: 1.08;
        color: var(--ink);
    }
    .lp-h1 span, .lp-h2 span { color: var(--accent); }
    .lp-h2 {
        font-size: clamp(1.6rem, 3vw, 2.25rem);
        font-weight: 750;
        letter-spacing: -0.028em;
        line-height: 1.18;
        color: var(--ink);
    }
    .lp-lead {
        font-size: 0.96rem;
        color: var(--ink-2);
        line-height: 1.65;
    }

    /* Hero card */
    .lp-hero-card {
        margin-top: 1.5rem;
        border-radius: 26px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: clamp(1.5rem, 4vw, 3rem);
    }
    .lp-hero-grid {
        display: grid;
        gap: 2rem;
        align-items: center;
    }
    @media (min-width: 900px) {
        .lp-hero-grid {
            grid-template-columns: minmax(0, 1.12fr) minmax(0, 0.88fr);
        }
    }
    .lp-hero-visual {
        border-radius: 20px;
        border: 1px solid var(--line);
        background: var(--bg);
        padding: 1.4rem;
    }
    .lp-chip-row {
        margin-top: 2rem;
        padding-top: 1.35rem;
        border-top: 1px solid var(--line);
        display: grid;
        gap: 0.65rem;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    .lp-chip {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.6rem 0.85rem;
        border-radius: 12px;
        border: 1px solid var(--line);
        background: var(--sunken);
        font-size: 12.5px;
        font-weight: 500;
        color: var(--ink-2);
    }

    /* Simulator */
    .lp-sim-wrap {
        border-radius: 26px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: clamp(1.4rem, 3.5vw, 2.5rem);
    }
    .lp-sim-grid {
        display: grid;
        gap: 1.5rem;
        margin-top: 1.5rem;
    }
    @media (min-width: 860px) {
        .lp-sim-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
    }
    .lp-sim-box {
        border-radius: 18px;
        border: 1px solid var(--line);
        background: var(--bg);
        padding: 1.35rem 1.45rem;
    }
    .lp-slider-row { margin-bottom: 1rem; }
    .lp-slider-row:last-child { margin-bottom: 0; }
    .lp-slider-head {
        display: flex;
        justify-content: space-between;
        font-size: 12.5px;
        margin-bottom: 0.35rem;
    }
    .lp-slider {
        width: 100%;
        accent-color: var(--ink);
        cursor: pointer;
    }

    /* 4 Rules Grid (02_metodologi.png style) */
    .lp-rules-layout {
        display: grid;
        gap: 2rem;
        align-items: start;
    }
    @media (min-width: 900px) {
        .lp-rules-layout { grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.45fr); }
    }
    .lp-rules-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    }
    .lp-rule-card {
        border-radius: 18px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: 1.35rem 1.4rem;
    }
    .lp-rule-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        margin-bottom: 0.7rem;
    }

    /* Role Switcher */
    .lp-tabs {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        padding: 0.25rem;
        border-radius: 9999px;
        border: 1px solid var(--line);
        background: var(--surface);
    }
    .lp-tab {
        border: none;
        background: transparent;
        color: var(--ink-2);
        padding: 0.42rem 0.95rem;
        border-radius: 9999px;
        font-size: 12.5px;
        font-weight: 500;
        cursor: pointer;
        transition: all 140ms ease;
    }
    .lp-tab.is-active {
        background: var(--ink);
        color: var(--surface);
        font-weight: 600;
    }
    .lp-role-panel {
        margin-top: 1.35rem;
        border-radius: 22px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: clamp(1.35rem, 3vw, 2.2rem);
    }
    .lp-role-items {
        margin-top: 1.15rem;
        display: grid;
        gap: 0.75rem;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    }
    .lp-role-item {
        border-radius: 14px;
        border: 1px solid var(--line);
        background: var(--bg);
        padding: 0.95rem 1.05rem;
    }

    /* Bottom CTA */
    .lp-cta-banner {
        border-radius: 22px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: clamp(1.5rem, 3.5vw, 2.25rem);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
    }
    .lp-footer {
        padding: 2rem 0 2.5rem;
        border-top: 1px solid var(--line);
        font-size: 12.5px;
        color: var(--ink-3);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }
</style>

<div class="lp-page">
    <header class="lp-nav">
        <div class="lp-wrap lp-nav-in">
            <a href="{{ route('landing') }}" class="lp-brand">
                <x-brand-logo :size="32" />
                <span>Peringatan Dini</span>
            </a>

            <nav class="lp-links" aria-label="Navigasi utama">
                <a href="#proyeksi">Proyeksi</a>
                <a href="#simulasi">Simulasi</a>
                <a href="#aturan">Aturan FTII</a>
                <a href="#peran">Peran</a>
                <a href="{{ route('metodologi') }}">Metodologi</a>
            </nav>

            <div style="display:flex;align-items:center;gap:0.55rem;">
                <button type="button" class="btn btn-quiet btn-sm" data-theme-toggle aria-label="Ganti tema" style="min-height:36px;padding:0.35rem 0.6rem;">
                    <x-icon name="sun" class="hidden dark:block" /><x-icon name="moon" class="dark:hidden" />
                </button>
                @auth
                    <a href="{{ route('beranda') }}" class="lp-pill-cta">
                        <span>Buka Dasbor</span>
                        <span class="lp-pill-cta-dot" aria-hidden="true">&nearr;</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="lp-pill-cta">
                        <span>Masuk Portal</span>
                        <span class="lp-pill-cta-dot" aria-hidden="true">&nearr;</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main class="lp-wrap">
        {{-- 1. HERO --}}
        <section id="proyeksi" class="lp-sec" style="padding-top:1.75rem;">
            <div class="lp-hero-card">
                <div class="lp-hero-grid">
                    <div>
                        <p class="lp-eyebrow">FTII UHAMKA &middot; Sistem Peringatan Dini Akademik</p>
                        <h1 class="lp-h1">Pantau <span>Risiko Nilaimu</span> Lebih Awal</h1>
                        <p class="lp-lead" style="margin-top:1rem;max-width:490px;">
                            Cek proyeksi nilai semester berjalan, hitung target UAS minimal agar lulus aman, dan pantau 20 SKS Pengayaan serta 4 syarat wajib SKPI sebelum nilai akhir terbit.
                        </p>

                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.75rem;margin-top:1.5rem;">
                            @auth
                                <a href="{{ route('beranda') }}" class="lp-pill-cta" style="padding:0.5rem 0.55rem 0.5rem 1.25rem;font-size:0.9rem;">
                                    <span>Lanjut ke Dasbor</span>
                                    <span class="lp-pill-cta-dot">&nearr;</span>
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="lp-pill-cta" style="padding:0.5rem 0.55rem 0.5rem 1.25rem;font-size:0.9rem;">
                                    <span>Masuk dengan NIM</span>
                                    <span class="lp-pill-cta-dot">&nearr;</span>
                                </a>
                                <a href="{{ route('tiket.form') }}" class="btn btn-quiet">Ajukan Akun (E-Ticket)</a>
                            @endauth
                        </div>

                        <p class="text-xs text-ink-3" style="margin-top:1rem;">
                            Cakupan 5 prodi FTII: Teknik Informatika &middot; STI &middot; Teknik Elektro &middot; Teknik Mesin &middot; Teknik Industri
                        </p>
                    </div>

                    <div class="lp-hero-visual">
                        <div style="display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--ink-3);padding-bottom:0.75rem;border-bottom:1px solid var(--line);">
                            <span class="font-medium text-ink-2">Ringkasan Proyeksi Semester</span>
                            <span class="mono">Skala 0 – 10</span>
                        </div>

                        <div style="display:flex;align-items:baseline;justify-content:space-between;margin-top:0.9rem;">
                            <div>
                                <p class="text-xs text-ink-3">Status Berjalan</p>
                                <p style="font-size:1.35rem;font-weight:750;color:var(--ok);">Risiko rendah</p>
                            </div>
                            <p class="mono" style="font-size:1.85rem;font-weight:750;">1,2 <span class="text-xs font-normal text-ink-3">/ 10</span></p>
                        </div>

                        <div style="display:flex;gap:3px;height:7px;margin-top:0.75rem;">
                            <div style="flex:25;background:#10b981;border-radius:9999px;"></div>
                            <div style="flex:25;background:#f59e0b;border-radius:9999px;"></div>
                            <div style="flex:50;background:#ef4444;border-radius:9999px;"></div>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.6rem;margin-top:1rem;">
                            <div style="border-radius:12px;border:1px solid var(--line);background:var(--surface);padding:0.7rem;">
                                <p class="text-xs text-ink-3">Target UAS</p>
                                <p class="mono text-xs font-semibold text-ink" style="margin-top:3px;">A &ge; 65</p>
                            </div>
                            <div style="border-radius:12px;border:1px solid var(--line);background:var(--surface);padding:0.7rem;">
                                <p class="text-xs text-ink-3">Pengayaan</p>
                                <p class="mono text-xs font-semibold text-ink" style="margin-top:3px;">14/20 SKS</p>
                            </div>
                            <div style="border-radius:12px;border:1px solid var(--line);background:var(--surface);padding:0.7rem;">
                                <p class="text-xs text-ink-3">Syarat SKPI</p>
                                <p class="mono text-xs font-semibold" style="margin-top:3px;color:var(--ok);">4/4 Tuntas</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lp-chip-row">
                    <div class="lp-chip"><x-icon name="chart-line-up" size="size-4" /><span>Proyeksi Target UAS</span></div>
                    <div class="lp-chip"><x-icon name="brain" size="size-4" /><span>Analisis AI Zero-PII</span></div>
                    <div class="lp-chip"><x-icon name="graduation-cap" size="size-4" /><span>20 SKS &amp; 4 Syarat SKPI</span></div>
                    <div class="lp-chip"><x-icon name="users" size="size-4" /><span>Pendampingan DPA</span></div>
                    <div class="lp-chip"><x-icon name="shield-check" size="size-4" /><span>Audit Validator AI</span></div>
                </div>
            </div>
        </section>

        {{-- 2. SIMULATOR INTERAKTIF --}}
        <section id="simulasi" class="lp-sec" style="padding-top:1rem;">
            <div class="lp-sim-wrap">
                <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1rem;">
                    <div>
                        <p class="lp-eyebrow">Simulasi Langsung Rumus FTII UHAMKA</p>
                        <h2 class="lp-h2">Geser angka untuk cek <span>target UAS minimal</span> kamu.</h2>
                    </div>
                    <button type="button" id="sim-reset" class="btn btn-quiet btn-sm">Reset Contoh</button>
                </div>

                <div class="lp-sim-grid">
                    <div class="lp-sim-box">
                        <div class="lp-slider-row">
                            <div class="lp-slider-head"><span class="font-medium">Keaktifan (10%)</span><strong class="mono" id="v-akt">85</strong></div>
                            <input type="range" id="s-akt" min="0" max="100" value="85" class="lp-slider">
                        </div>
                        <div class="lp-slider-row">
                            <div class="lp-slider-head"><span class="font-medium">Tugas (20%)</span><strong class="mono" id="v-tgs">80</strong></div>
                            <input type="range" id="s-tgs" min="0" max="100" value="80" class="lp-slider">
                        </div>
                        <div class="lp-slider-row">
                            <div class="lp-slider-head"><span class="font-medium">UTS (30%)</span><strong class="mono" id="v-uts">75</strong></div>
                            <input type="range" id="s-uts" min="0" max="100" value="75" class="lp-slider">
                        </div>
                        <div class="lp-slider-row">
                            <div class="lp-slider-head"><span class="font-medium">Kehadiran (% syarat ujian min. 75%)</span><strong class="mono" id="v-hdr">88%</strong></div>
                            <input type="range" id="s-hdr" min="40" max="100" value="88" class="lp-slider">
                        </div>
                    </div>

                    <div class="lp-sim-box" style="display:flex;flex-direction:column;justify-content:space-between;gap:1rem;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div>
                                <p class="text-xs text-ink-3">Rata-rata Sementara</p>
                                <p class="mono" id="out-avg" style="font-size:2rem;font-weight:750;color:var(--ink);margin-top:2px;">78,3</p>
                                <p class="text-xs font-semibold" id="out-status" style="color:var(--ok);">Jalur aman</p>
                            </div>
                            <div>
                                <p class="text-xs text-ink-3">Target UAS Terbaik</p>
                                <p class="mono" id="out-best" style="font-size:1.55rem;font-weight:750;color:var(--accent);margin-top:6px;">A &ge; 83</p>
                                <p class="text-xs text-ink-3" id="out-cheer">Fokus kejar A!</p>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.6rem;padding-top:1rem;border-top:1px solid var(--line);">
                            <div style="border-radius:12px;border:1px solid var(--line);background:var(--surface);padding:0.65rem;text-align:center;">
                                <p class="text-xs text-ink-3">Lulus C (56)</p>
                                <p class="mono text-sm font-bold text-ink" id="out-c" style="margin-top:2px;">23</p>
                            </div>
                            <div style="border-radius:12px;border:1px solid var(--line);background:var(--surface);padding:0.65rem;text-align:center;">
                                <p class="text-xs text-ink-3">Target B (68)</p>
                                <p class="mono text-sm font-bold text-ink" id="out-b" style="margin-top:2px;">53</p>
                            </div>
                            <div style="border-radius:12px;border:1px solid var(--line);background:var(--surface);padding:0.65rem;text-align:center;">
                                <p class="text-xs text-ink-3">Target A (80)</p>
                                <p class="mono text-sm font-bold" id="out-a" style="margin-top:2px;color:var(--ok);">83</p>
                            </div>
                        </div>
                        <p class="text-xs text-ink-3" id="out-note">Bobot resmi KHS UHAMKA: Keaktifan 10% + Tugas 20% + UTS 30% + UAS 40%.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- 3. ATURAN FTII (02_metodologi.png layout) --}}
        <section id="aturan" class="lp-sec">
            <div class="lp-rules-layout">
                <div>
                    <p class="lp-eyebrow">Cara Skor 0–10 Dihitung</p>
                    <h2 class="lp-h2">Transparan dan sesuai <span>aturan FTII UHAMKA.</span></h2>
                    <p class="lp-lead" style="margin-top:0.85rem;">
                        Semakin kecil angkanya, semakin santai dan aman jalur kuliahmu. Nilai yang belum keluar tidak pernah dihitung nol — sistem hanya membaca komponen yang sudah tersedia.
                    </p>
                    <a href="{{ route('metodologi') }}" class="btn btn-quiet btn-sm" style="margin-top:1.25rem;">Baca Metodologi Lengkap &rarr;</a>
                </div>

                <div class="lp-rules-grid">
                    <div class="lp-rule-card">
                        <div class="lp-rule-top"><span class="mono font-bold text-accent">01.</span><span class="mono text-ink-3">Bobot 75%</span></div>
                        <h3 class="text-base font-semibold text-ink">Nilai &amp; Absensi Semester Berjalan</h3>
                        <p class="text-xs text-ink-2" style="margin-top:0.5rem;line-height:1.6;">Memakai komposisi resmi FTII: UAS 40%, UTS 30%, Tugas 20%, dan Keaktifan 10%. Hadir &lt; 75% pada kelas reguler langsung menaikkan skor minimal ke 3,0 (Sedang).</p>
                    </div>
                    <div class="lp-rule-card">
                        <div class="lp-rule-top"><span class="mono font-bold text-accent">02.</span><span class="mono text-ink-3">Bobot 25%</span></div>
                        <h3 class="text-base font-semibold text-ink">Riwayat Akademik Semester Lalu</h3>
                        <p class="text-xs text-ink-2" style="margin-top:0.5rem;line-height:1.6;">Menganalisis IPK kumulatif (aman &ge; 3,00), tren IPS, jumlah mata kuliah mengulang (D/E), dan lonjakan SKS. Di awal semester, bobot otomatis seimbang 50% : 50%.</p>
                    </div>
                    <div class="lp-rule-card">
                        <div class="lp-rule-top"><span class="mono font-bold text-accent">03.</span><span class="mono text-ink-3">+0 s.d. 1,0 poin</span></div>
                        <h3 class="text-base font-semibold text-ink">Pendampingan &amp; Catatan DPA</h3>
                        <p class="text-xs text-ink-2" style="margin-top:0.5rem;line-height:1.6;">DPA mendokumentasikan perwalian dan memberi saran tindak lanjut. Khusus semester 7 ke atas, jeda bimbingan tugas akhir di atas 30 hingga 60 hari menambah indikator peringatan.</p>
                    </div>
                    <div class="lp-rule-card">
                        <div class="lp-rule-top"><span class="mono font-bold text-accent">04.</span><span class="mono text-ink-3">+0 s.d. 2,0 poin</span></div>
                        <h3 class="text-base font-semibold text-ink">MK Pengayaan 20 SKS &amp; 4 Syarat SKPI</h3>
                        <p class="text-xs text-ink-2" style="margin-top:0.5rem;line-height:1.6;">Memantau cicilan 20 SKS MK Pengayaan (19 non-kelas bebas absen 75%, 3 coaching reguler) serta unggah bukti &amp; verifikasi 4 syarat wajib SKPI (IT, BAPS, PKM min. Universitas, TOEFL UHAMKA).</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- 4. 4 PERAN --}}
        <section id="peran" class="lp-sec" style="padding-top:1rem;">
            <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1rem;">
                <div>
                    <p class="lp-eyebrow">Empat Peran Terintegrasi</p>
                    <h2 class="lp-h2">Satu sistem yang nyaman dipakai <span>mahasiswa hingga fakultas.</span></h2>
                </div>
                <div class="lp-tabs" role="tablist" aria-label="Pilih peran">
                    <button type="button" class="lp-tab is-active" data-role="mhs">Mahasiswa</button>
                    <button type="button" class="lp-tab" data-role="dpa">DPA</button>
                    <button type="button" class="lp-tab" data-role="val">Validator AI</button>
                    <button type="button" class="lp-tab" data-role="adm">Admin FTII</button>
                </div>
            </div>

            <div class="lp-role-panel" id="role-panel"></div>
        </section>

        {{-- 5. BOTTOM CTA --}}
        <section class="lp-sec" style="padding-top:1rem;">
            <div class="lp-cta-banner">
                <div>
                    <h2 class="text-xl font-bold text-ink">Siap pantau nilaimu dengan lebih tenang?</h2>
                    <p class="text-sm text-ink-2" style="margin-top:0.3rem;">Masuk dengan NIM/ID pegawai FTII UHAMKA atau ajukan aktivasi akun lewat E-Ticket.</p>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:0.65rem;">
                    <a href="{{ route('login') }}" class="lp-pill-cta" style="padding:0.48rem 0.55rem 0.48rem 1.15rem;">
                        <span>Masuk Portal</span>
                        <span class="lp-pill-cta-dot">&nearr;</span>
                    </a>
                    <a href="{{ route('tiket.form') }}" class="btn btn-quiet">Ajukan E-Ticket</a>
                </div>
            </div>
        </section>

        <footer class="lp-footer">
            <span>Cegah Sebelum Terlambat &middot; Fakultas Teknologi Industri dan Informatika UHAMKA</span>
            <div style="display:flex;gap:1.25rem;">
                <a href="{{ route('metodologi') }}" class="hover:underline" style="color:inherit;text-decoration:none;">Metodologi</a>
                <a href="{{ route('tiket.form') }}" class="hover:underline" style="color:inherit;text-decoration:none;">E-Ticket Akun</a>
                <a href="{{ route('login') }}" class="hover:underline" style="color:inherit;text-decoration:none;">Masuk</a>
            </div>
        </footer>
    </main>
</div>

<script>
(function () {
    const sAkt = document.getElementById('s-akt');
    const sTgs = document.getElementById('s-tgs');
    const sUts = document.getElementById('s-uts');
    const sHdr = document.getElementById('s-hdr');
    function calcSim() {
        const akt = Number(sAkt.value), tgs = Number(sTgs.value), uts = Number(sUts.value), hdr = Number(sHdr.value);
        document.getElementById('v-akt').textContent = akt;
        document.getElementById('v-tgs').textContent = tgs;
        document.getElementById('v-uts').textContent = uts;
        document.getElementById('v-hdr').textContent = hdr + '%';

        const pre = 0.10 * akt + 0.20 * tgs + 0.30 * uts;
        const avg = (pre / 0.60).toFixed(1).replace('.', ',');
        const minC = Math.max(0, Math.ceil((56 - pre) / 0.40));
        const minB = Math.max(0, Math.ceil((68 - pre) / 0.40));
        const minA = Math.max(0, Math.ceil((80 - pre) / 0.40));

        document.getElementById('out-avg').textContent = avg;
        document.getElementById('out-c').textContent = minC > 100 ? '>100' : minC;
        document.getElementById('out-b').textContent = minB > 100 ? '>100' : minB;
        document.getElementById('out-a').textContent = minA > 100 ? '>100' : minA;

        const stEl = document.getElementById('out-status');
        if (hdr < 75) {
            stEl.textContent = 'Waspada: Hadir < 75%';
            stEl.style.color = 'var(--bad)';
        } else if (minC > 70) {
            stEl.textContent = 'Risiko tinggi';
            stEl.style.color = 'var(--bad)';
        } else if (minC >= 40) {
            stEl.textContent = 'Risiko sedang';
            stEl.style.color = 'var(--warn)';
        } else {
            stEl.textContent = 'Jalur aman';
            stEl.style.color = 'var(--ok)';
        }

        const bestEl = document.getElementById('out-best');
        const cheerEl = document.getElementById('out-cheer');
        if (minA <= 100) {
            bestEl.textContent = 'A ≥ ' + minA;
            bestEl.style.color = 'var(--ok)';
            cheerEl.textContent = minA <= 75 ? 'Jaga tetap ≥ 80!' : 'Fokus kejar A!';
        } else if (minB <= 100) {
            bestEl.textContent = 'B ≥ ' + minB;
            bestEl.style.color = 'var(--accent)';
            cheerEl.textContent = 'Jangan lengah, kejar B!';
        } else if (minC <= 100) {
            bestEl.textContent = 'C ≥ ' + minC;
            bestEl.style.color = 'var(--warn)';
            cheerEl.textContent = 'Fokus UAS, jangan lengah!';
        } else {
            bestEl.textContent = 'Evaluasi';
            bestEl.style.color = 'var(--bad)';
            cheerEl.textContent = 'Diskusi bersama DPA!';
        }
    }
    [sAkt, sTgs, sUts, sHdr].forEach(el => el.addEventListener('input', calcSim));
    document.getElementById('sim-reset').addEventListener('click', () => {
        sAkt.value = 85; sTgs.value = 80; sUts.value = 75; sHdr.value = 88; calcSim();
    });
    calcSim();

    const roles = {
        mhs: {
            eyebrow: 'Peran Mahasiswa',
            title: 'Pantau nilai berjalan, 20 SKS pengayaan & 4 syarat SKPI.',
            desc: 'Impor PDF KHS UHAMKA atau input manual langsung dari HP untuk mengetahui target UAS, menerima saran belajar AI, mengunggah bukti SKPI, dan membaca catatan bimbingan dari DPA.',
            items: [
                ['Target UAS Minimal', 'Otomatis menghitung skor UAS untuk mencapai A, B, atau C berdasarkan bobot 40/30/20/10.'],
                ['20 SKS & 4 Syarat SKPI', 'Lacak 22 katalog MK Pengayaan serta unggah bukti sertifikat IT, BAPS, PKM, dan TOEFL UHAMKA.'],
                ['Catatan & Pengingat', 'Baca catatan bimbingan terbaru dari DPA dan aktifkan notifikasi email bila risiko meningkat.']
            ]
        },
        dpa: {
            eyebrow: 'Peran Dosen Pembimbing Akademik (DPA)',
            title: 'Pendampingan bimbingan yang fokus, cepat, dan terdokumentasi.',
            desc: 'Pantau seluruh mahasiswa bimbingan secara langsung, telusuri riwayat nilai lintas semester, verifikasi bukti sertifikat SKPI, dan arsipkan seluruh catatan konsultasi.',
            items: [
                ['Antrean Prioritas', 'Langsung melihat mahasiswa berisiko tinggi yang perlu segera dihubungi untuk bimbingan.'],
                ['Verifikasi Bukti SKPI', 'Buka berkas sertifikat privat mahasiswa dan beri keputusan Verifikasi atau Minta Perbaiki.'],
                ['Riwayat Bimbingan', 'Menu arsip khusus dengan pencarian nama/NIM/topik, filter status, dan periode waktu.']
            ]
        },
        val: {
            eyebrow: 'Peran Validator AI (Human-in-the-Loop)',
            title: 'Audit kebenaran skor AI secara objektif dan terukur.',
            desc: 'Bandingkan skor Gemini AI berdampingan dengan Pembanding Matematis (RiskModel) beserta 5 indikator akademik, lalu ekspor laporan akurasi CSV.',
            items: [
                ['Perbandingan Side-by-Side', 'Melihat skor AI vs rumus matematis serta menandai prioritas bila selisih ≥ 1,5.'],
                ['Panduan Koreksi Jelas', 'Keputusan Sesuai atau Perlu Koreksi dilengkapi opsi terapkan koreksi langsung ke mahasiswa.'],
                ['Laporan Audit CSV', 'Rekam jejak validasi akurasi AI siap diunduh untuk evaluasi prodi dan penjaminan mutu.']
            ]
        },
        adm: {
            eyebrow: 'Peran Admin Sekretariat FTII',
            title: 'Kendali penuh dasbor fakultas, E-Ticket akun, dan verifikasi SKPI.',
            desc: 'Kelola seluruh akun pengguna, setujui pengajuan E-Ticket sambil menetapkan DPA, tinjau antrean bukti SKPI, serta pantau statistik risiko lintas prodi.',
            items: [
                ['Dasbor Eksekutif FTII', 'Sebaran risiko per prodi/angkatan, rekap beban DPA, kesehatan mesin AI, dan ekspor CSV.'],
                ['E-Ticket Tanpa Sandi Mentah', 'Persetujuan akun dengan badge antrean di sidebar dan tautan atur sandi sekali pakai ke email.'],
                ['Verifikasi SKPI Terpusat', 'Pencarian nama/NIM, filter periode & jenis SKPI, serta paginasi agar tetap cepat lintas angkatan.']
            ]
        }
    };

    const panel = document.getElementById('role-panel');
    function renderRole(key) {
        const r = roles[key] || roles.mhs;
        panel.innerHTML = `
            <p class="lp-eyebrow">${r.eyebrow}</p>
            <h3 class="text-xl font-bold text-ink">${r.title}</h3>
            <p class="text-sm text-ink-2" style="margin-top:0.45rem;max-width:680px;line-height:1.6;">${r.desc}</p>
            <div class="lp-role-items">
                ${r.items.map(([t, d]) => `<div class="lp-role-item"><p class="text-sm font-semibold text-ink">${t}</p><p class="text-xs text-ink-2" style="margin-top:0.35rem;line-height:1.55;">${d}</p></div>`).join('')}
            </div>
        `;
    }
    document.querySelectorAll('.lp-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.lp-tab').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            renderRole(btn.dataset.role);
        });
    });
    renderRole('mhs');
})();
</script>
@endsection
