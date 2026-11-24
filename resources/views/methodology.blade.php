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
        border-radius: 12px;
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
</style>

<header class="sticky top-0 z-30 border-b border-line bg-bg/90 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-8">
        <a href="{{ route('landing') }}" class="font-semibold tracking-tight">Cegah Sebelum Terlambat</a>
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
                    <span class="mt-badge mono">70%</span>
                </div>
                <ul class="mt-3 list-disc space-y-1.5 pl-4 text-sm text-ink-2">
                    <li>Tiap matkul memakai bobot <strong>UAS 40%</strong>, <strong>UTS 25%</strong>, <strong>Tugas 25%</strong>, dan <strong>Kehadiran 10%</strong>.</li>
                    <li>Nilai yang belum keluar (kosong) <strong>tidak dihitung nol</strong> — dilewati otomatis.</li>
                    <li>Sebelum UAS, sistem menghitung <strong>target nilai UAS minimal</strong> agar lulus C (56) atau B (68).</li>
                    <li>Jika ada matkul kelas biasa dengan hadir <strong>&lt; 75%</strong>, skor langsung naik minimal <strong>3,0 (Sedang)</strong>.</li>
                </ul>
            </div>

            <div class="mt-card">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-medium text-ink">Riwayat Semester Lalu</h3>
                    <span class="mt-badge mono">20%</span>
                </div>
                <ul class="mt-3 list-disc space-y-1.5 pl-4 text-sm text-ink-2">
                    <li>Melihat <strong>IPK kumulatif</strong> (aman di 3,00 ke atas), penurunan <strong>IPS</strong>, jumlah matkul mengulang (D/E), dan lonjakan SKS.</li>
                    <li>Di awal semester (saat nilai baru sedikit), porsi riwayat otomatis naik menjadi <strong>50%</strong> agar hasil tetap stabil dan tidak menebak-nebak.</li>
                </ul>
            </div>

            <div class="mt-card">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-medium text-ink">Catatan DPA</h3>
                    <span class="mt-badge mono">10%</span>
                </div>
                <ul class="mt-3 list-disc space-y-1.5 pl-4 text-sm text-ink-2">
                    <li>Keaktifan mahasiswa saat bimbingan akademik (Aktif, Cukup, atau Kurang).</li>
                    <li>Khusus <strong>semester 7 ke atas</strong>, ikut memantau rutinitas bimbingan tugas akhir/skripsi.</li>
                </ul>
            </div>

            <div class="mt-card">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-medium text-ink">MK Pengayaan 20 SKS &amp; PKM</h3>
                    <span class="mt-badge mono">+0–2,0 poin</span>
                </div>
                <ul class="mt-3 list-disc space-y-1.5 pl-4 text-sm text-ink-2">
                    <li>Khusus <strong>semester 5 ke atas</strong>: menambah poin peringatan jika cicilan wajib <strong>20 SKS Pengayaan</strong> tertinggal atau wajib <strong>PKM</strong> (batas semester 7) belum berjalan.</li>
                    <li><strong>19 MK Pengayaan non-kelas</strong> bebas dari syarat absen 75% dan tersinkron otomatis dari KHS.</li>
                    <li><strong>3 MK kelas coaching</strong> (<em>Keterampilan Komunikasi</em> &amp; 2 MK <em>Bahasa Jepang</em>) tetap mengikuti aturan absensi kelas.</li>
                </ul>
            </div>
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
        <details class="mt-card">
            <summary class="cursor-pointer text-base font-medium text-ink">Lihat rincian ambang angka &amp; skala nilai UHAMKA</summary>
            <p class="mt-3 text-xs text-ink-2">
                Skala huruf UHAMKA: <strong>A</strong> (80–100 / 4,0), <strong>B</strong> (68–79 / 3,0), <strong>C</strong> (56–67 / 2,0), <strong>D</strong> (45–55 / 1,0), <strong>E</strong> (0–44 / 0,0).
            </p>

            <div class="mt-grid-2 mt-5">
                <div class="overflow-x-auto">
                    <h3 class="text-sm font-medium text-ink">Risiko per Mata Kuliah</h3>
                    <table class="tbl mt-2 w-full text-xs">
                        <thead><tr><th>Tingkat</th><th>Sebelum UAS (Target C)</th><th>Setelah UAS / KHS</th></tr></thead>
                        <tbody>
                            <tr><td>Rendah (0)</td><td>UAS Min &lt; 40</td><td>Nilai &ge; 68 (B ke atas)</td></tr>
                            <tr><td>Sedang (0,5)</td><td>UAS Min 40 – 70</td><td>Nilai 56 – 67,9 (C)</td></tr>
                            <tr><td>Tinggi (1,0)</td><td>UAS Min &gt; 70 / hadir &lt; 75%</td><td>Nilai &lt; 56 (D atau E)</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="overflow-x-auto">
                    <h3 class="text-sm font-medium text-ink">Band Riwayat &amp; Input DPA</h3>
                    <table class="tbl mt-2 w-full text-xs">
                        <thead><tr><th>Variabel</th><th>Aman (0)</th><th>Waspada (0,5)</th><th>Kritis (1,0)</th></tr></thead>
                        <tbody>
                            <tr><td>IPK kumulatif</td><td>&ge; 3,00</td><td>2,00 – 2,99</td><td>&lt; 2,00</td></tr>
                            <tr><td>Penurunan IPS</td><td>Tidak turun</td><td>Turun &lt; 0,50</td><td>Turun &ge; 0,50</td></tr>
                            <tr><td>MK mengulang (D/E)</td><td>0 MK</td><td>1 MK</td><td>&ge; 2 MK</td></tr>
                            <tr><td>Kenaikan beban SKS</td><td>&le; +3 SKS</td><td>+4 s.d. +6 SKS</td><td>&gt; +6 SKS</td></tr>
                            <tr><td>Keaktifan (DPA)</td><td>Aktif</td><td>Cukup</td><td>Kurang</td></tr>
                            <tr><td>Tugas akhir sem 7+</td><td>&le; 30 hari lalu</td><td>31 – 60 hari</td><td>&gt; 60 hari / belum</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
    </section>
</main>
@endsection