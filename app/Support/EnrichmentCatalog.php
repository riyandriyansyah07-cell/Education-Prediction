<?php

namespace App\Support;

use App\Models\EnrichmentRecord;
use Illuminate\Support\Collection;

/**
 * Katalog resmi 22 Mata Kuliah Pengayaan FTII UHAMKA (Panduan 2025)
 * beserta logika pencocokan nama KHS, evaluasi progres target wajib 20 SKS,
 * dan perhitungan penalti risiko khusus semester 5 ke atas.
 */
final class EnrichmentCatalog
{
    public const TARGET_SKS = 20;

    public const MIN_CONVERSION_SEMESTER = 5;

    /**
     * @return list<array{
     *   code:string,
     *   name:string,
     *   credits:int,
     *   competency:string,
     *   lecturer:string,
     *   is_class_based:bool,
     *   activities:string
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'code' => 'berpikir-kritis-kreatif',
                'name' => 'Berpikir Kritis dan Kreatif',
                'credits' => 2,
                'competency' => 'Mobilitas & Kompetensi / Kompetisi',
                'lecturer' => 'Arry Avorizano, S.Kom., M.Kom',
                'is_class_based' => false,
                'activities' => 'Magang, Project Independent/Capstone Design, PKM (RE/RSH/PI/VGK/GFT), PILMAPRES, Menulis Buku/Artikel Media Massa, ONMIPA-PT, Satria Data',
            ],
            [
                'code' => 'empati-kecerdasan-sosial',
                'name' => 'Empati dan Kecerdasan Sosial',
                'credits' => 2,
                'competency' => 'Minat, Bakat & Pengabdian Masyarakat',
                'lecturer' => 'Erizal, S.Kom., M.Kom.',
                'is_class_based' => false,
                'activities' => 'PHP2D/Wira Desa/Bina Desa/PKM-PM, Relawan Kemanusiaan, Donor Darah (>=5x), Takmir/Marbot, Panitia Acara Sosial, KPPS, Kegiatan Kemasyarakatan/Bela Negara',
            ],
            [
                'code' => 'etika-profesional',
                'name' => 'Etika Profesional',
                'credits' => 2,
                'competency' => 'Mobilitas dan Kompetensi',
                'lecturer' => 'Akhmad Rizal Dzikrillah, S.Kom., MTI',
                'is_class_based' => false,
                'activities' => 'Pelatihan Kepribadian, Diklat Etika Profesi, Magang (1 semester)',
            ],
            [
                'code' => 'inovasi-pemikiran-desain',
                'name' => 'Inovasi dan Pemikiran Desain',
                'credits' => 3,
                'competency' => 'Mobilitas, Pengabdian, Kompetisi & Kewirausahaan',
                'lecturer' => 'Delvis Agusman, S.T., M.Sc.',
                'is_class_based' => false,
                'activities' => 'Magang Inovasi, Pengabdian Solusi Inovasi, Produk Inovatif, Kompetisi Desain, HAKI, Paten, Kewirausahaan Inovatif, PKM-KC/KI, KRI/KRTI, KMHE, KKCTBN',
            ],
            [
                'code' => 'keberagaman-multibudaya',
                'name' => 'Keberagaman dan Multibudaya',
                'credits' => 2,
                'competency' => 'Minat, Bakat & Pengabdian Masyarakat',
                'lecturer' => 'Rosalina, S.T., M.T.',
                'is_class_based' => false,
                'activities' => 'Pengisi Acara Budaya & Seni, Pegiat Kebudayaan, Pertukaran Budaya, Magang di luar bidang prodi',
            ],
            [
                'code' => 'kepemimpinan-inklusif',
                'name' => 'Kepemimpinan Inklusif',
                'credits' => 3,
                'competency' => 'Kepemimpinan dan Kewirausahaan',
                'lecturer' => 'Arif Hamzah, M.A.',
                'is_class_based' => false,
                'activities' => 'Pengurus HIMA/BEM/DPM/IMM/UKM/PKM Centre, Pengurus Karang Taruna/DKM/Ortom/LSM, LKMM, Imam Masjid, Pelatih/Pembimbing Manasik Haji/Umroh',
            ],
            [
                'code' => 'retorika-presentasi-publik',
                'name' => 'Keterampilan Retorika dan Presentasi Publik',
                'credits' => 2,
                'competency' => 'Kepemimpinan, Kompetisi & Mobilitas',
                'lecturer' => 'Firman Noor Hasan, S.Kom., M.TI.',
                'is_class_based' => false,
                'activities' => 'Penyuluh, Da\'i/Khotib/Muballigh, Brand Ambassador, Review Produk, Tes Bahasa Asing (setara TOEFL >=450), KDMI/NUDC, Kompetisi Orasi, Magang',
            ],
            [
                'code' => 'kewirausahaan-sosial',
                'name' => 'Kewirausahaan Sosial',
                'credits' => 2,
                'competency' => 'Minat, Bakat & Pengabdian Masyarakat',
                'lecturer' => 'Atiqah Meutia Hilda, S.Kom., M.Kom.',
                'is_class_based' => false,
                'activities' => 'Sociopreneurship, Pengelola Usaha Sosial, Pemberdayaan Keluarga Duafa',
            ],
            [
                'code' => 'kewirausahaan-terstruktur',
                'name' => 'Kewirausahaan Terstruktur',
                'credits' => 3,
                'competency' => 'Kepemimpinan & Kompetisi',
                'lecturer' => 'Faldy Irwiensyah, S.Kom., M.Kom.',
                'is_class_based' => false,
                'activities' => 'Usaha Bisnis dengan Mitra (CV/PT/Start-Up), Bisnis Berjalan, Penggerak Usaha Mikro, Start-Up, KBMI/KMI Expo/KIBM',
            ],
            [
                'code' => 'pemecahan-masalah',
                'name' => 'Pemecahan Masalah',
                'credits' => 2,
                'competency' => 'Mobilitas & Pengabdian Masyarakat',
                'lecturer' => 'Mia Kamayani, S.Kom., M.Kom.',
                'is_class_based' => false,
                'activities' => 'Magang, Studi Independen minimal 40 jam, UHAMKA Mengajar/Bina Desa/KKN, Teknologi Tepat Guna',
            ],
            [
                'code' => 'penelitian',
                'name' => 'Penelitian',
                'credits' => 2,
                'competency' => 'Mobilitas dan Kompetensi',
                'lecturer' => 'Dr. Dan Mugisidi, S.T., M.Si.',
                'is_class_based' => false,
                'activities' => 'Kolaborasi Penelitian Mahasiswa-Dosen, Penelitian Mahasiswa-Mahasiswa, MBKM Penelitian (minimal bukti submit luaran)',
            ],
            [
                'code' => 'pengembangan-kompetensi',
                'name' => 'Pengembangan Kompetensi',
                'credits' => 3,
                'competency' => 'Mobilitas & Kompetisi',
                'lecturer' => 'Hendi Saryanto, S.T., M.Sc.',
                'is_class_based' => false,
                'activities' => 'Uji Kompetensi/Sertifikasi (Internasional/LSP), Microsoft Certificate TUK Mahira, LIDM, GEMASTIK, Magang',
            ],
            [
                'code' => 'pengembangan-profesi',
                'name' => 'Pengembangan Profesi',
                'credits' => 2,
                'competency' => 'Mobilitas dan Kompetensi',
                'lecturer' => 'Yos Nofendri, S.Pd., MSME.',
                'is_class_based' => false,
                'activities' => 'Magang, Asisten Laboratorium (1 semester penuh), Pelatihan Profesi (>=20 jam), TOEFL Prediction (>=450)',
            ],
            [
                'code' => 'pengembangan-talenta',
                'name' => 'Pengembangan Talenta',
                'credits' => 3,
                'competency' => 'Minat Bakat, Mobilitas & Kompetisi',
                'lecturer' => 'Emilia Roza, S.T., M.Pd., M.T.',
                'is_class_based' => false,
                'activities' => 'Seni/Olahraga, Peksiminas/POMNAS/MTQMN/LPSMN, Pengisi Acara, Mengajar/Membina Ekskul/Bimbel/Baca Al-Qur\'an (minimal 3–6 bulan)',
            ],
            [
                'code' => 'publikasi-nasional',
                'name' => 'Publikasi Nasional',
                'credits' => 2,
                'competency' => 'Mobilitas dan Kompetensi',
                'lecturer' => 'Dr. Dwi Astuti Cahyasiwi, S.T., M.T.',
                'is_class_based' => false,
                'activities' => 'PKM-AI, Jurnal Nasional Terakreditasi (Sinta 1–6), Jurnal Nasional, Seminar Nasional/Pemakalah (melibatkan dosen FTII)',
            ],
            [
                'code' => 'publikasi-internasional',
                'name' => 'Publikasi Internasional',
                'credits' => 3,
                'competency' => 'Mobilitas dan Kompetensi',
                'lecturer' => 'Dr. Dan Mugisidi, S.T., M.Si.',
                'is_class_based' => false,
                'activities' => 'Jurnal Internasional atau Seminar Internasional (melibatkan dosen FTII sebagai penulis)',
            ],
            [
                'code' => 'strategi-negosiasi',
                'name' => 'Strategi Negosiasi',
                'credits' => 2,
                'competency' => 'Kepemimpinan dan Kewirausahaan',
                'lecturer' => 'Nunik Pratiwi, S.T., M.Kom.',
                'is_class_based' => false,
                'activities' => 'Mendapatkan Mitra Kerjasama/Mitra Usaha, Mendapatkan Sponsorship (dengan proposal & bukti kerjasama)',
            ],
            [
                'code' => 'summer-course',
                'name' => 'Summer Course',
                'credits' => 3,
                'competency' => 'Mobilitas dan Kompetensi',
                'lecturer' => 'Harry Ramza, S.T., M.T., Ph.D.',
                'is_class_based' => false,
                'activities' => 'Summer Course, Pertukaran Pelajar/Student Exchange, Short Course, Outbound Program',
            ],
            [
                'code' => 'kewirausahaan-bisnis',
                'name' => 'Kewirausahaan Bisnis',
                'credits' => 2,
                'competency' => 'Kepemimpinan dan Kewirausahaan',
                'lecturer' => 'Dr. Dan Mugisidi, S.T., M.Si.',
                'is_class_based' => false,
                'activities' => 'PKM-K, KBMK, Kegiatan Wirausaha Pemula (NIB / Surat Keterangan RT/RW & Laporan Laba-Rugi)',
            ],
            [
                'code' => 'keterampilan-komunikasi',
                'name' => 'Keterampilan Komunikasi',
                'credits' => 2,
                'competency' => 'Mobilitas dan Kompetensi',
                'lecturer' => 'Dr. Nurlina Rahman, S.Pd., M.Si.',
                'is_class_based' => true,
                'activities' => 'Kelas coaching praktis berkomunikasi (diambil langsung melalui KRS)',
            ],
            [
                'code' => 'keterampilan-berbahasa-jepang',
                'name' => 'Keterampilan Berbahasa Jepang',
                'credits' => 2,
                'competency' => 'Mobilitas dan Kompetensi',
                'lecturer' => 'Dr. Rita Agustina Karnawati, M.Pd.',
                'is_class_based' => true,
                'activities' => 'Kelas coaching bahasa Jepang (satu paket dengan Keterampilan Berbicara Bahasa Jepang)',
            ],
            [
                'code' => 'keterampilan-berbicara-jepang',
                'name' => 'Keterampilan Berbicara Bahasa Jepang',
                'credits' => 2,
                'competency' => 'Mobilitas dan Kompetensi',
                'lecturer' => 'Dr. Rita Agustina Karnawati, M.Pd.',
                'is_class_based' => true,
                'activities' => 'Kelas coaching berbicara bahasa Jepang (satu paket dengan Keterampilan Berbahasa Jepang)',
            ],
        ];
    }

    /** @return array<string,mixed>|null */
    public static function find(string $code): ?array
    {
        foreach (self::all() as $item) {
            if ($item['code'] === $code) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Cocokkan nama mata kuliah dari KHS/impor dengan katalog resmi 22 MK Pengayaan FTII UHAMKA.
     *
     * @return array<string,mixed>|null
     */
    public static function matchCourseName(string $courseName): ?array
    {
        $normalized = self::normalizeName($courseName);
        if ($normalized === '') {
            return null;
        }

        $aliases = [
            'penelitian 1' => 'penelitian',
            'publikasi 1' => 'publikasi-nasional',
            'empati dan kecedasan sosial' => 'empati-kecerdasan-sosial',
        ];
        if (isset($aliases[$normalized])) {
            return self::find($aliases[$normalized]);
        }

        foreach (self::all() as $item) {
            if (self::normalizeName($item['name']) === $normalized) {
                return $item;
            }
        }

        return null;
    }

    private static function normalizeName(string $raw): string
    {
        $s = mb_strtolower(trim($raw));
        $s = str_replace(['&', '-', '_', '/', '(', ')', '.'], ' ', $s);
        $s = (string) preg_replace('/\bdan\b/u', 'dan', $s);
        $s = (string) preg_replace('/\s+/u', ' ', $s);

        return trim($s);
    }

    /**
     * Hitung kontribusi poin risiko (0.0 s.d. 1.5) dari keterlambatan progres 20 SKS Pengayaan.
     * Khusus berlaku mulai semester 5 ke atas agar mahasiswa semester 1-4 tidak kena penalti palsu.
     *
     * @param  array<string,mixed>  $progress
     */
    public static function riskPenalty(array $progress): float
    {
        $semester = (int) ($progress['current_semester'] ?? 1);
        $converted = (int) ($progress['converted_sks'] ?? 0);
        $effective = (int) ($progress['effective_sks'] ?? 0);

        if ($semester < self::MIN_CONVERSION_SEMESTER || $converted >= self::TARGET_SKS) {
            return 0.0;
        }

        if ($semester <= 6) {
            $expected = $semester === 5 ? 10 : 14;

            return $effective < $expected ? 0.6 : 0.0;
        }

        // Semester 7 ke atas (fase kritis kelulusan)
        return $effective >= self::TARGET_SKS ? 0.8 : 1.5;
    }

    /**
     * Hitung progres 20 SKS Pengayaan beserta peringatan dini berdasarkan semester mahasiswa saat ini.
     *
     * @param  Collection<int,EnrichmentRecord>  $records
     * @return array{
     *   target_sks:int,
     *   converted_sks:int,
     *   ready_sks:int,
     *   in_progress_sks:int,
     *   effective_sks:int,
     *   remaining_converted:int,
     *   remaining_to_collect:int,
     *   converted_pct:float,
     *   ready_pct:float,
     *   in_progress_pct:float,
     *   current_semester:int,
     *   phase_label:string,
     *   risk_level:string,
     *   status_message:string
     * }
     */
    public static function evaluateProgress(Collection $records, int $currentSemester): array
    {
        $semester = max(1, min(14, $currentSemester));

        $converted = (int) $records
            ->filter(fn (EnrichmentRecord $r) => $r->status === EnrichmentRecord::STATUS_DIKONVERSI && strtoupper((string) $r->grade) !== 'E')
            ->sum('credits');

        $ready = (int) $records
            ->where('status', EnrichmentRecord::STATUS_SIAP)
            ->sum('credits');

        $inProgress = (int) $records
            ->where('status', EnrichmentRecord::STATUS_DICICIL)
            ->sum('credits');

        $effective = $converted + $ready;
        $target = self::TARGET_SKS;

        $remainingConverted = max(0, $target - $converted);
        $remainingToCollect = max(0, $target - $effective);

        $convertedPct = min(100.0, round(($converted / $target) * 100, 1));
        $readyPct = min(max(0.0, 100.0 - $convertedPct), round(($ready / $target) * 100, 1));
        $inProgressPct = min(max(0.0, 100.0 - $convertedPct - $readyPct), round(($inProgress / $target) * 100, 1));

        if ($semester <= 4) {
            $phaseLabel = 'Fase Cicil Bukti (Semester 1–4)';
            if ($effective >= $target) {
                $riskLevel = 'rendah';
                $message = 'Luar biasa! Bukti kegiatan pengayaanmu sudah mencapai 20 SKS dan siap dikonversi ke KRS mulai semester 5.';
            } elseif ($semester >= 3 && ($effective + $inProgress) === 0) {
                $riskLevel = 'sedang';
                $message = 'Kamu sudah di semester '.$semester.' tetapi belum mencicil kegiatan pengayaan. Mulai kumpulkan 2–3 SKS per semester agar tidak menumpuk di semester 5 ke atas.';
            } else {
                $riskLevel = 'rendah';
                $message = 'Kamu berada di fase cicil. Kumpulkan bukti kegiatan dan konsultasikan sejak dini agar siap dikonversi ke KRS mulai semester 5.';
            }
        } elseif ($semester <= 6) {
            $phaseLabel = 'Fase Konversi KRS (Semester 5–6)';
            $expected = $semester === 5 ? 10 : 14;
            if ($converted >= $target) {
                $riskLevel = 'rendah';
                $message = 'Target wajib 20 SKS MK Pengayaan sudah tuntas dikonversi di KRS!';
            } elseif ($effective >= $expected) {
                $riskLevel = 'rendah';
                $message = "Progresmu di jalur aman ({$converted} SKS terkonversi, {$ready} SKS siap rekognisi). Pastikan bukti siap rekognisi segera di-KRS-kan dengan Dosen Pengampu.";
            } else {
                $riskLevel = 'sedang';
                $message = "Di semester {$semester}, idealnya minimal {$expected} SKS sudah terkumpul/terkonversi. Saat ini baru {$effective} SKS — segera rencanakan kegiatan tambahan.";
            }
        } else {
            $phaseLabel = 'Fase Kritis Kelulusan (Semester 7+)';
            if ($converted >= $target) {
                $riskLevel = 'rendah';
                $message = 'Selamat! Seluruh 20 SKS wajib MK Pengayaan sudah tuntas dikonversi dan tidak menghambat kelulusanmu.';
            } elseif ($effective >= $target) {
                $riskLevel = 'sedang';
                $message = "Bukti 20 SKS sudah terkumpul ({$converted} SKS terkonversi, {$ready} SKS siap rekognisi). Segera urus konversi KRS dan penilaian ke Dosen Pengampu sebelum UAS.";
            } else {
                $riskLevel = 'tinggi';
                $message = "Perhatian: di semester {$semester}, kamu masih kekurangan {$remainingToCollect} SKS bukti pengayaan (baru {$converted} SKS terkonversi). Segera konsultasi dengan DPA dan Dosen Pengampu agar kelulusan tidak tertunda.";
            }
        }

        return [
            'target_sks' => $target,
            'converted_sks' => $converted,
            'ready_sks' => $ready,
            'in_progress_sks' => $inProgress,
            'effective_sks' => $effective,
            'remaining_converted' => $remainingConverted,
            'remaining_to_collect' => $remainingToCollect,
            'converted_pct' => $convertedPct,
            'ready_pct' => $readyPct,
            'in_progress_pct' => $inProgressPct,
            'current_semester' => $semester,
            'phase_label' => $phaseLabel,
            'risk_level' => $riskLevel,
            'status_message' => $message,
        ];
    }
}