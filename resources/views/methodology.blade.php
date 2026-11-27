@extends('layouts.base')

@section('title', 'Cara risiko dihitung')

@section('body')
<style>
    .mt-grid-3 { display: grid; grid-template-columns: 1fr; gap: 0.9rem; }
    .mt-grid-2 { display: grid; grid-template-columns: 1fr; gap: 1rem; }
    @media (min-width: 768px) {
        .mt-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .mt-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .mt-card {
        border: 1px solid var(--line);
        background: var(--surface);
        border-radius: 18px;
        padding: 1.25rem;
    }
    .mt-level-ok { border-top: 3px solid var(--ok); }
    .mt-level-warn { border-top: 3px solid var(--warn); }
    .mt-level-bad { border-top: 3px solid var(--bad); }
    .mt-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.6rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
        background: color-mix(in oklab, var(--accent) 12%, var(--bg));
        color: var(--accent);
        border: 1px solid color-mix(in oklab, var(--accent) 28%, var(--line));
    }
    .mt-example {
        border: 1px solid var(--line);
        background: var(--bg);
        border-radius: 10px;
        padding: 1rem 1.15rem;
    }
    .mt-grades {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.9rem;
    }
    .mt-grade-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.7rem;
        border-radius: 8px;
        border: 1px solid var(--line);
        background: var(--bg);
        font-size: 0.76rem;
        color: var(--ink-2);
    }
    .mt-grade-chip strong { color: var(--ink); font-family: var(--font-mono); }
    .mt-table-box {
        margin-top: 1.15rem;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: var(--bg);
        overflow: hidden;
    }
    .mt-table-head {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--line);
        background: var(--sunken);
        font-size: 0.84rem;
        font-weight: 600;
        color: var(--ink);
    }
    .mt-ref-tbl {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
    }
    .mt-ref-tbl th {
        text-align: left;
        padding: 0.65rem 1rem;
        font-size: 0.74rem;
        font-weight: 600;
        color: var(--ink-3);
        border-bottom: 1px solid var(--line);
        background: color-mix(in oklab, var(--sunken) 45%, var(--bg));
    }
    .mt-ref-tbl td {
        padding: 0.7rem 1rem;
        border-bottom: 1px solid var(--line);
        color: var(--ink-2);
        vertical-align: middle;
    }
    .mt-ref-tbl tr:last-child td { border-bottom: 0; }
    .mt-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 999px;
        margin-right: 0.45rem;
        vertical-align: middle;
    }
</style>

<header class="sticky top-0 z-30 border-b border-line bg-bg/90 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-8">
        <a href="{{ route('landing') }}" class="inline-flex items-center gap-2.5 font-semibold tracking-tight"><x-brand-logo :size="28" /><span>Cegah Sebelum Terlambat</span></a>
        <div class="flex items-center gap-3">
            @auth
                <a href="{{ route('beranda') }}" class="btn btn-primary btn-sm">Kembali ke Dasbor</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary btn-sm">Masuk</a>
            @endauth
        </div>
    </div>
</header>

<main class="mx-auto max-w-5xl px-4 py-12 sm:px-8">
    <h1 class="rise text-3xl sm:text-4xl" style="letter-spacing:-0.03em;">Cara kerja perhitungan risiko</h1>
    <p class="rise mt-3 max-w-[64ch] text-ink-2" style="--i: 1">
        Sistem memberi skor peringatan dini dari <strong>0 sampai 10</strong>. Semakin kecil angkanya semakin aman — semakin besar angkanya, semakin cepat kamu perlu bertindak sebelum UAS.
    </p>

    {{-- 1. Arti 3 Warna Risiko --}}
    <section class="mt-9">
        <h2 class="text-xl font-medium">1. Arti 3 tingkat risiko</h2>
        <div class="mt-grid-3 mt-4">
            <div class="mt-card mt-level-ok">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold" style="color:var(--ok);">Risiko Rendah</span>
                    <span class="mono text-xs text-ink-3">0 – 2,4</span>
                </div>
                <p class="mt-2 text-sm text-ink-2">Nilai, kehadiran, dan progres kuliah berada di jalur aman. Cukup pertahankan ritme belajarmu.</p>
            </div>
            <div class="mt-card mt-level-warn">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold" style="color:var(--warn);">Risiko Sedang</span>
                    <span class="mono text-xs text-ink-3">2,5 – 4,9</span>
                </div>
                <p class="mt-2 text-sm text-ink-2">Mulai ada tanda bahaya (misalnya target UAS berat atau absen mendekati batas). Masih sempat diperbaiki sebelum UAS.</p>
            </div>
            <div class="mt-card mt-level-bad">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold" style="color:var(--bad);">Risiko Tinggi</span>
                    <span class="mono text-xs text-ink-3">5,0 – 10</span>
                </div>
                <p class="mt-2 text-sm text-ink-2">Kondisi kritis yang berpotensi menurunkan IPS atau menghambat kelulusan. Segera kejar nilai dan temui DPA.</p>
            </div>
        </div>
    </section>

    {{-- 2. 4 Sumber Utama Perhitungan --}}
    <section class="mt-12">
        <h2 class="text-xl font-medium">2. Dari mana skor 0–10 dihitung?</h2>
        <p class="mt-1 text-sm text-ink-2">Sistem menggabungkan empat sumber data secara otomatis:</p>

        <div class="mt-grid-2 mt-4">
            <div class="mt-card">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-medium text-ink">Nilai &amp; Absensi Semester Ini</h3>
                    <span class="mt-badge mono">75%</span>
                </div>
                <ul class="mt-3 list-disc space-y-1.5 pl-4 text-sm text-ink-2">
                    <li>Tiap matkul memakai bobot <strong>UAS 40%</strong>, <strong>UTS 30%</strong>, <strong>Tugas 20%</strong>, dan <strong>Kehadiran 10%</strong>.</li>
                    <li>Nilai yang belum keluar (kosong) <strong>tidak dihitung nol</strong> — dilewati otomatis.</li>
                    <li>Sebelum UAS, sistem menghitung <strong>target nilai UAS minimal</strong> agar lulus C (56) atau B (68).</li>
                    <li>Jika ada matkul kelas biasa dengan hadir <strong>&lt; 75%</strong>, skor langsung naik minimal <strong>3,0 (Sedang)</strong>.</li>
                </ul>
            </div>

            <div class="mt-card">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-medium text-ink">Riwayat Semester Lalu</h3>
                    <span class="mt-badge mono">25%</span>
                </div>
                <ul class="mt-3 list-disc space-y-1.5 pl-4 text-sm text-ink-2">
                    <li>Melihat <strong>IPK kumulatif</strong> (aman di 3,00 ke atas), penurunan <strong>IPS</strong>, jumlah matkul mengulang (D/E), dan lonjakan SKS.</li>
                    <li>Di awal semester (saat nilai baru sedikit), bobot otomatis diseimbangkan menjadi <strong>50% : 50%</strong> agar hasil tetap stabil dan tidak menebak-nebak.</li>
                </ul>
            </div>

            <div class="mt-card">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-medium text-ink">Pendampingan DPA</h3>
                    <span class="mt-badge mono">+0–1,0 poin</span>
                </div>
                <ul class="mt-3 list-disc space-y-1.5 pl-4 text-sm text-ink-2">
                    <li>DPA berperan sebagai pendamping akademik dan pemberi saran (tidak mengubah nilai keaktifan kelas mahasiswa).</li>
                    <li>Khusus <strong>semester 7 ke atas</strong>, pemantauan bimbingan tugas akhir/skripsi dapat menambah <strong>+0,5 s.d. +1,0 poin</strong> peringatan bila belum mulai atau lama tidak bimbingan (&gt; 30 hari).</li>
                </ul>
            </div>

            <div class="mt-card">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-medium text-ink">MK Pengayaan 20 SKS &amp; 4 Syarat SKPI</h3>
                    <span class="mt-badge mono">+0–2,0 poin</span>
                </div>
                <ul class="mt-3 list-disc space-y-1.5 pl-4 text-sm text-ink-2">
                    <li>Khusus <strong>semester 5 ke atas</strong>: menambah poin peringatan jika cicilan wajib <strong>20 SKS Pengayaan</strong> tertinggal atau wajib <strong>PKM</strong> (diikuti tiap semester ganjil mulai Sem 1) belum lolos minimal <strong>tahap Universitas</strong> agar memperoleh Sertifikat PKM (SKPI); lolos tahap Fakultas saja belum diakui SKPI.</li>
                    <li>Memantau kelengkapan <strong>4 Syarat Wajib SKPI FTII</strong>: Sertifikat Kompetensi IT (<em>Mahira / BNSP</em>), Baitul Arqam Purna Studi (<em>BAPS</em>), Sertifikat PKM (min. tahap Universitas), dan Sertifikat TOEFL khusus <em>UHAMKA</em>.</li>
                    <li><strong>19 MK Pengayaan non-kelas</strong> bebas dari syarat absen 75% dan tersinkron otomatis dari KHS, sedangkan <strong>3 MK kelas coaching</strong> tetap mengikuti aturan absensi.</li>
                </ul>
            </div>
        </div>

        <div class="mt-card mt-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-medium text-ink">Evaluasi Hibrida Gemini AI, Guardrail &amp; Audit Validator AI</h3>
                <span class="mt-badge">Human-in-the-Loop</span>
            </div>
            <ul class="mt-3 list-disc space-y-1.5 pl-4 text-sm text-ink-2">
                <li>Semester terbaru dievaluasi dengan <strong>Gemini AI</strong> (Structured JSON Output, Zero-PII) yang diperkuat <strong>guardrail anti-halusinasi</strong> (menyaring faktor yang bertentangan dengan angka KHS asli) serta cadangan otomatis ke rumus matematis <strong>RiskModel</strong>.</li>
                <li>Peran khusus <strong>Validator AI</strong> membandingkan skor Gemini AI berdampingan dengan pembanding matematis <em>RiskModel</em> untuk mengaudit akurasi, mencatat koreksi, dan mengekspor laporan audit CSV.</li>
            </ul>
        </div>
    </section>

    {{-- 3. Contoh Singkat --}}
    <section class="mt-12">
        <h2 class="text-xl font-medium">3. Contoh mudah</h2>
        <div class="mt-grid-2 mt-4">
            <div class="mt-example">
                <p class="text-sm font-medium text-ink">Nilai aman, tapi MK Pengayaan masih dicicil</p>
                <p class="mt-1.5 text-sm text-ink-2">
                    Mahasiswa semester 6 dengan semua nilai A/B dan absen aman, namun baru punya 7 dari 20 SKS Pengayaan → skor sekitar <strong class="mono">0,6 / 10 (Rendah)</strong> disertai pengingat ringan untuk mencicil sisa SKS.
                </p>
            </div>
            <div class="mt-example">
                <p class="text-sm font-medium text-ink">Nilai bagus, tapi ada 1 matkul kelas bolos</p>
                <p class="mt-1.5 text-sm text-ink-2">
                    Nilai UTS tinggi, tetapi ada satu mata kuliah kelas biasa dengan kehadiran <strong class="mono">65%</strong> → skor otomatis minimal <strong class="mono">3,0 / 10 (Sedang)</strong> sebagai alarm sebelum terancam tidak bisa ikut UAS.
                </p>
            </div>
        </div>
    </section>

    {{-- 4. Detail Teknis (Opsional untuk Dosen / Penguji) --}}
    <section class="mt-12">
        <details class="ui-acc mt-card" style="padding:1.15rem 1.35rem;">
            <summary class="ui-acc-head">
                <span class="ui-acc-left">
                    <span class="ui-acc-icon"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 4.5h5a2.5 2.5 0 0 1 2.5 2.5v9a2 2 0 0 0-2-2h-5.5v-9.5z"/><path d="M16.5 4.5h-5a2.5 2.5 0 0 0-2.5 2.5v9a2 2 0 0 1 2-2h5.5v-9.5z"/></svg></span>
                    <span>
                        <span class="ui-acc-title">Rincian Ambang Angka &amp; Skala Nilai UHAMKA</span>
                        <span class="ui-acc-sub">Tabel konversi huruf A–E, batas kehadiran, dan band riwayat akademik</span>
                    </span>
                </span>
                <svg class="ui-acc-chev" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 7.5L10 12.5L15 7.5"/></svg>
            </summary>

            <div class="mt-grades">
                <span class="mt-grade-chip"><strong>A</strong> 80–100 (4,0)</span>
                <span class="mt-grade-chip"><strong>B</strong> 68–79 (3,0)</span>
                <span class="mt-grade-chip"><strong>C</strong> 56–67 (2,0)</span>
                <span class="mt-grade-chip"><strong>D</strong> 45–55 (1,0)</span>
                <span class="mt-grade-chip"><strong>E</strong> 0–44 (0,0)</span>
            </div>

            <div class="mt-table-box">
                <div class="mt-table-head">Risiko per Mata Kuliah</div>
                <div class="overflow-x-auto">
                    <table class="mt-ref-tbl">
                        <thead>
                            <tr>
                                <th style="width:24%;">Tingkat (Poin)</th>
                                <th style="width:38%;">Sebelum UAS (Target Nilai C)</th>
                                <th style="width:38%;">Setelah UAS / KHS Resmi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-medium text-ink"><span class="mt-dot" style="background:var(--ok);"></span>Rendah <span class="mono text-ink-3">(0)</span></td>
                                <td class="mono">UAS Min &lt; 40</td>
                                <td>Nilai <span class="mono">&ge; 68</span> (B ke atas)</td>
                            </tr>
                            <tr>
                                <td class="font-medium text-ink"><span class="mt-dot" style="background:var(--warn);"></span>Sedang <span class="mono text-ink-3">(0,5)</span></td>
                                <td class="mono">UAS Min 40 – 70</td>
                                <td>Nilai <span class="mono">56 – 67,9</span> (C)</td>
                            </tr>
                            <tr>
                                <td class="font-medium text-ink"><span class="mt-dot" style="background:var(--bad);"></span>Tinggi <span class="mono text-ink-3">(1,0)</span></td>
                                <td><span class="mono">UAS Min &gt; 70</span> atau hadir <span class="mono">&lt; 75%</span></td>
                                <td>Nilai <span class="mono">&lt; 56</span> (D atau E)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-table-box">
                <div class="mt-table-head">Band Riwayat Akademik &amp; Penilaian DPA</div>
                <div class="overflow-x-auto">
                    <table class="mt-ref-tbl">
                        <thead>
                            <tr>
                                <th style="width:34%;">Variabel</th>
                                <th style="width:22%;"><span class="mt-dot" style="background:var(--ok);"></span>Aman (0)</th>
                                <th style="width:22%;"><span class="mt-dot" style="background:var(--warn);"></span>Waspada (0,5)</th>
                                <th style="width:22%;"><span class="mt-dot" style="background:var(--bad);"></span>Kritis (1,0)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-medium text-ink">IPK kumulatif</td>
                                <td class="mono">&ge; 3,00</td>
                                <td class="mono">2,00 – 2,99</td>
                                <td class="mono">&lt; 2,00</td>
                            </tr>
                            <tr>
                                <td class="font-medium text-ink">Penurunan IPS</td>
                                <td>Tidak turun</td>
                                <td>Turun <span class="mono">&lt; 0,50</span></td>
                                <td>Turun <span class="mono">&ge; 0,50</span></td>
                            </tr>
                            <tr>
                                <td class="font-medium text-ink">MK mengulang (D/E)</td>
                                <td class="mono">0 MK</td>
                                <td class="mono">1 MK</td>
                                <td class="mono">&ge; 2 MK</td>
                            </tr>
                            <tr>
                                <td class="font-medium text-ink">Kenaikan beban SKS</td>
                                <td class="mono">&le; +3 SKS</td>
                                <td class="mono">+4 s.d. +6 SKS</td>
                                <td class="mono">&gt; +6 SKS</td>
                            </tr>
                            <tr>
                                <td class="font-medium text-ink">Tugas akhir sem 7+ (DPA)</td>
                                <td>&le; 30 hari lalu</td>
                                <td>31 – 60 hari lalu</td>
                                <td>&gt; 60 hari / belum mulai</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
    </section>
</main>
@endsection