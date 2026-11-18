@extends('layouts.base')

@section('title', 'Cara risiko dihitung')

@section('body')
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
    <h1 class="rise text-3xl sm:text-4xl">Cara risiko akademik dihitung</h1>
    <p class="rise mt-3 max-w-[68ch] text-ink-2" style="--i: 1">
        Tingkat risiko dihitung dari proyeksi nilai akhir selama semester berjalan dan 10 variabel inti, dilengkapi label tingkat kepercayaan sumber datanya.
    </p>

    <section class="mt-10">
        <h2 class="text-2xl">1. Komponen &amp; Bobot Proyeksi per Mata Kuliah</h2>
        <p class="mt-2 max-w-[65ch] text-sm text-ink-2">
            Nilai sementara (N) memakai komponen yang sudah tersedia dengan pembagian ulang bobot otomatis. Sebelum UAS, sistem juga menghitung nilai UAS minimal (U_min) agar nilai akhir mencapai batas C (56) atau B (68) sesuai skala nilai UHAMKA (A: 80–100, B: 68–79, C: 56–67, D: 45–55, E: 0–44).
        </p>
        <div class="mt-4 overflow-x-auto">
            <table class="tbl min-w-[38rem]">
                <thead><tr><th>Komponen</th><th>Bobot</th><th>Sumber Skor (0–100)</th><th>Tingkat Kepercayaan Data</th></tr></thead>
                <tbody>
                    <tr><td>UAS</td><td class="num mono">40%</td><td>Isian mahasiswa; diganti nilai resmi saat KHS terbit</td><td>Dilaporkan sendiri, lalu Terverifikasi KHS</td></tr>
                    <tr><td>UTS</td><td class="num mono">25%</td><td>Nilai UTS yang diisi mahasiswa</td><td>Dilaporkan sendiri</td></tr>
                    <tr><td>Tugas</td><td class="num mono">25%</td><td>Rata-rata tugas yang sudah dinilai (mandiri / rekap OLU / GDrive)</td><td>Dilaporkan sendiri atau Terkonfirmasi dosen</td></tr>
                    <tr><td>Kehadiran</td><td class="num mono">10%</td><td>Persentase kehadiran sistem akademik (syarat ujian minimal 75%)</td><td>Terverifikasi sistem akademik</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-12">
        <h2 class="text-2xl">2. Ambang Risiko Mata Kuliah &amp; Mahasiswa</h2>
        <div class="mt-4 grid gap-8 lg:grid-cols-2">
            <div class="overflow-x-auto">
                <h3 class="text-lg font-medium">Tingkat Mata Kuliah</h3>
                <table class="tbl mt-2">
                    <thead><tr><th>Tingkat (Poin)</th><th>Sebelum UAS (U_min untuk C)</th><th>Setelah UAS / KHS (Nilai N)</th></tr></thead>
                    <tbody>
                        <tr><td>Rendah (0)</td><td>Di bawah 40</td><td>68 atau lebih (B ke atas)</td></tr>
                        <tr><td>Sedang (0,5)</td><td>40 sampai 70</td><td>56 sampai 67,9 (C)</td></tr>
                        <tr><td>Tinggi (1,0)</td><td>Di atas 70 (atau kehadiran &lt; 75%)</td><td>Di bawah 56 (D atau E)</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="overflow-x-auto">
                <h3 class="text-lg font-medium">Gabungan Skor Mahasiswa (0–10)</h3>
                <table class="tbl mt-2">
                    <thead><tr><th>Bagian</th><th>Bobot</th><th>Cakupan Variabel</th></tr></thead>
                    <tbody>
                        <tr><td>Semester berjalan</td><td class="num mono">70%</td><td>Rata-rata poin risiko mata kuliah tertimbang SKS</td></tr>
                        <tr><td>Riwayat akademik</td><td class="num mono">20%</td><td>IPK kumulatif, tren IPS, MK mengulang (D/E), kenaikan beban SKS</td></tr>
                        <tr><td>Input DPA</td><td class="num mono">10%</td><td>Keaktifan (Aktif/Cukup/Kurang) &amp; progres tugas akhir (sem 7+)</td></tr>
                    </tbody>
                </table>
                <p class="mt-2 text-xs text-ink-3">Ambang skor mahasiswa: Rendah (&lt; 2,5), Sedang (2,5 – 4,9), Tinggi (&ge; 5,0).</p>
            </div>
        </div>
    </section>

    <section class="mt-12">
        <h2 class="text-2xl">3. Band Riwayat &amp; Penilaian DPA</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="tbl min-w-[42rem]">
                <thead><tr><th>Variabel</th><th>Aman (0 poin)</th><th>Waspada (0,5 poin)</th><th>Kritis (1,0 poin)</th></tr></thead>
                <tbody>
                    <tr><td>IPK kumulatif</td><td>3,00 atau lebih</td><td>2,00 sampai 2,99</td><td>Di bawah 2,00</td></tr>
                    <tr><td>Tren IPS</td><td>Tidak turun</td><td>Turun kurang dari 0,50</td><td>Turun 0,50 atau lebih</td></tr>
                    <tr><td>Mata kuliah mengulang (D/E)</td><td>0 mata kuliah</td><td>1 mata kuliah</td><td>2 mata kuliah atau lebih</td></tr>
                    <tr><td>Kenaikan beban SKS</td><td>Naik 3 SKS atau kurang</td><td>Naik 4 sampai 6 SKS</td><td>Naik lebih dari 6 SKS</td></tr>
                    <tr><td>Keaktifan bimbingan (DPA)</td><td>Aktif</td><td>Cukup</td><td>Kurang</td></tr>
                    <tr><td>Tugas akhir sem 7+ (DPA)</td><td>Bimbingan &le; 30 hari lalu</td><td>31 sampai 60 hari lalu</td><td>Lebih dari 60 hari / belum mulai</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection
