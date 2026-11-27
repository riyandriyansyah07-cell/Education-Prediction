<?php

namespace App\Support;

use App\Models\EnrichmentRecord;
use App\Models\StudentSetting;
use Illuminate\Support\Collection;

/**
 * Katalog resmi 22 Mata Kuliah Pengayaan FTII UHAMKA (Panduan 2025)
 * beserta logika pencocokan nama KHS, evaluasi progres target wajib 20 SKS,
 * pemantauan syarat wajib PKM (paling lambat semester 7), dan bobot risiko semester 5+.
 */
final class EnrichmentCatalog
{
    public const TARGET_SKS = 20;

    public const MIN_CONVERSION_SEMESTER = 5;

    public const PKM_DEADLINE_SEMESTER = 7;

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
     * Otomatis mengabaikan akhiran dalam tanda kurung seperti "(Kompetensi)", "(Komp.)", serta singkatan KHS.
     *
     * @return array<string,mixed>|null
     */
    public static function matchCourseName(string $courseName): ?array
    {
        // Hapus isi tanda kurung termasuk yang terpotong di KHS (mis. "(Ko)", "(Komp.)", "(Kompetensi")
        $withoutParens = (string) preg_replace('/\([^)]*\)?/u', ' ', $courseName);
        $normalized = self::normalizeName($withoutParens);
        if ($normalized === '') {
            return null;
        }

        // Hindari salah cocok dengan mata kuliah reguler FTII yang namanya mirip
        $excludedRegular = [
            'etika profesi',
            'metodologi penelitian',
            'penelitian operasional',
            'riset operasional',
        ];
        if (in_array($normalized, $excludedRegular, true)) {
            return null;
        }

        $aliases = [
            'penelitian 1' => 'penelitian',
            'publikasi 1' => 'publikasi-nasional',
            'empati dan kecedasan sosial' => 'empati-kecerdasan-sosial',
            'empati kecedasan sosial' => 'empati-kecerdasan-sosial',
            'ketrampilan komunikasi' => 'keterampilan-komunikasi',
            'keteramp berbicara bhs jepan' => 'keterampilan-berbicara-jepang',
            'keteramp berbicara bhs jepang' => 'keterampilan-berbicara-jepang',
            'keteramp berbahasa jepang' => 'keterampilan-berbahasa-jepang',
        ];
        if (isset($aliases[$normalized])) {
            return self::find($aliases[$normalized]);
        }

        $inputTokens = self::significantTokens($normalized);
        if ($inputTokens === []) {
            return null;
        }
        $inputJoined = implode(' ', $inputTokens);

        // 1. Pencocokan persis (mengabaikan perbedaan "&" vs "dan" serta tanda kurung)
        foreach (self::all() as $item) {
            $itemTokens = self::significantTokens(self::normalizeName($item['name']));
            if ($itemTokens === $inputTokens) {
                return $item;
            }
        }

        // 2. Pencocokan awalan nama (misal "Etika Profesional Kompetensi" atau "Pengembangan Profesi 1")
        foreach (self::all() as $item) {
            $itemJoined = implode(' ', self::significantTokens(self::normalizeName($item['name'])));
            if ($itemJoined !== '' && str_starts_with($inputJoined, $itemJoined.' ')) {
                return $item;
            }
        }

        // 3. Pencocokan singkatan KHS berbasis awalan kata (misal "Keteramp. Ret. & Presen. Publ." atau "Empati & Kecerd. Sosial")
        $rawHasAbbrevDot = str_contains($withoutParens, '.');
        $shortMap = [
            'bhs' => 'bahasa',
            'ket' => 'keterampilan',
            'ketrampilan' => 'keterampilan',
            'keteramp' => 'keterampilan',
            'kew' => 'kewirausahaan',
            'kewiraus' => 'kewirausahaan',
            'komp' => 'kompetensi',
            'pengemb' => 'pengembangan',
            'pemik' => 'pemikiran',
            'kepemimp' => 'kepemimpinan',
            'kecerd' => 'kecerdasan',
            'kecedasan' => 'kecerdasan',
            'presen' => 'presentasi',
            'publ' => 'publik',
            'ret' => 'retorika',
            'nas' => 'nasional',
            'inter' => 'internasional',
            'internas' => 'internasional',
            'prof' => 'profesi',
            'mas' => 'masalah',
            'neg' => 'negosiasi',
        ];

        $matchedItems = [];
        foreach (self::all() as $item) {
            $catTokens = self::significantTokens(self::normalizeName($item['name']));
            $catCount = count($catTokens);
            if ($catCount < 2 || count($inputTokens) < $catCount) {
                continue;
            }

            // Hanya izinkan akhiran tambahan yang umum di KHS (angka, "kompetensi", "ko", "konversi")
            if (count($inputTokens) > $catCount) {
                $extra = array_slice($inputTokens, $catCount);
                $allowedExtra = ['1', '2', 'ko', 'komp', 'kompetensi', 'konversi'];
                if (array_diff($extra, $allowedExtra) !== []) {
                    continue;
                }
            }

            $allMatch = true;
            $hasStrictPrefix = false;
            for ($i = 0; $i < $catCount; $i++) {
                $inTok = $inputTokens[$i];
                $inTok = $shortMap[$inTok] ?? $inTok;
                $catTok = $catTokens[$i];

                if ($inTok === $catTok) {
                    continue;
                }
                if (mb_strlen($inTok) >= 3 && str_starts_with($catTok, $inTok)) {
                    $hasStrictPrefix = true;
                    continue;
                }
                $allMatch = false;
                break;
            }

            if ($allMatch && ($catCount >= 3 || $rawHasAbbrevDot || ! $hasStrictPrefix)) {
                $matchedItems[] = $item;
            }
        }

        // Kembalikan hanya jika unik (tidak ambigu dengan MK lain)
        if (count($matchedItems) === 1) {
            return $matchedItems[0];
        }

        return null;
    }

    /** Deteksi apakah catatan pengayaan ini merupakan kegiatan PKM / PIMNAS. */
    public static function isPkmRecord(EnrichmentRecord $record): bool
    {
        $haystack = mb_strtolower($record->activity_type.' '.($record->evidence_notes ?? ''));

        return (bool) preg_match('/\b(pkm|pimnas)\b/u', $haystack);
    }

    private static function normalizeName(string $raw): string
    {
        $s = mb_strtolower(trim($raw));
        $s = str_replace(['&', '-', '_', '/', '(', ')', '.', ','], ' ', $s);
        $s = (string) preg_replace('/\s+/u', ' ', $s);

        return trim($s);
    }

    /** @return list<string> */
    private static function significantTokens(string $normalized): array
    {
        $parts = preg_split('/\s+/u', trim($normalized)) ?: [];
        $out = [];
        foreach ($parts as $tok) {
            if ($tok === '' || $tok === 'dan') {
                continue;
            }
            $out[] = $tok;
        }

        return $out;
    }

    /**
     * Hitung kontribusi poin risiko (0.0 s.d. 2.0) dari keterlambatan progres 20 SKS Pengayaan
     * dan syarat wajib PKM (paling lambat semester 7).
     * Khusus berlaku mulai semester 5 ke atas agar mahasiswa semester 1-4 tidak kena penalti palsu.
     *
     * @param  array<string,mixed>  $progress
     */
    public static function riskPenalty(array $progress): float
    {
        $semester = (int) ($progress['current_semester'] ?? 1);
        $converted = (int) ($progress['converted_sks'] ?? 0);
        $effective = (int) ($progress['effective_sks'] ?? 0);
        $pkmStatus = (string) ($progress['pkm_status'] ?? 'belum');

        if ($semester < self::MIN_CONVERSION_SEMESTER) {
            return 0.0;
        }

        $penalty = 0.0;

        if ($converted < self::TARGET_SKS) {
            if ($semester <= 6) {
                $expected = $semester === 5 ? 10 : 14;
                if ($effective < $expected) {
                    $penalty += 0.6;
                }
            } else {
                $penalty += $effective >= self::TARGET_SKS ? 0.8 : 1.5;
            }
        }

        if ($pkmStatus !== 'selesai') {
            if ($semester >= self::PKM_DEADLINE_SEMESTER) {
                $penalty += $pkmStatus === 'proses' ? 0.5 : 1.0;
            } elseif ($pkmStatus === 'belum') {
                $penalty += 0.4;
            }
        }

        return round(min(2.0, $penalty), 2);
    }

    /**
     * Hitung progres 20 SKS Pengayaan dan status wajib PKM (batas akhir semester 7).
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
     *   status_message:string,
     *   pkm_status:string,
     *   pkm_label:string,
     *   pkm_message:string
     * }
     */
    public static function evaluateProgress(Collection $records, int $currentSemester, ?StudentSetting $setting = null): array
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

        $pkmRecords = $records->filter(fn (EnrichmentRecord $r) => self::isPkmRecord($r));
        $hasCompletedPkm = $pkmRecords->contains(fn (EnrichmentRecord $r) => in_array($r->status, [EnrichmentRecord::STATUS_DIKONVERSI, EnrichmentRecord::STATUS_SIAP], true)
            && strtoupper((string) $r->grade) !== 'E');
        $hasOngoingPkm = $pkmRecords->contains(fn (EnrichmentRecord $r) => $r->status === EnrichmentRecord::STATUS_DICICIL);

        $explicitStage = (string) ($setting?->skpi_pkm_stage ?? StudentSetting::PKM_BELUM);

        if ($explicitStage === StudentSetting::PKM_PENDANAAN) {
            $pkmStatus = 'selesai';
            $pkmLabel = 'PKM Tuntas (Lolos Pendanaan)';
            $pkmMessage = 'Lolos tahap pendanaan PKM — memperoleh Sertifikat PKM yang diakui SKPI dan dapat diajukan rekognisi SKS.';
        } elseif ($explicitStage === StudentSetting::PKM_UNIVERSITAS) {
            $pkmStatus = 'selesai';
            $pkmLabel = 'PKM Tuntas (Lolos Universitas)';
            $pkmMessage = 'Lolos tahap Universitas — memperoleh Sertifikat PKM yang diakui sebagai syarat SKPI FTII.';
        } elseif ($explicitStage === StudentSetting::PKM_FAKULTAS) {
            $pkmStatus = 'proses';
            $pkmLabel = 'Lolos Fakultas (Belum SKPI)';
            $pkmMessage = 'Baru lolos tahap Fakultas (belum dapat dikonversi menjadi SKPI). Wajib lolos minimal tahap Universitas agar memperoleh Sertifikat PKM.';
        } elseif ($hasCompletedPkm) {
            $explicitStage = StudentSetting::PKM_UNIVERSITAS;
            $pkmStatus = 'selesai';
            $pkmLabel = 'PKM Tuntas';
            $pkmMessage = 'Syarat wajib PKM sudah terpenuhi dan diakui SKPI.';
        } elseif ($hasOngoingPkm) {
            $explicitStage = StudentSetting::PKM_FAKULTAS;
            $pkmStatus = 'proses';
            $pkmLabel = 'PKM Sedang Proses';
            $pkmMessage = $semester >= self::PKM_DEADLINE_SEMESTER
                ? 'PKM masih berstatus proses di semester '.$semester.' — wajib lolos minimal tahap Universitas agar memperoleh Sertifikat PKM (SKPI).'
                : 'PKM sedang berjalan (dibuka tiap semester ganjil mulai Sem 1); targetkan lolos minimal tahap Universitas agar diakui SKPI.';
        } else {
            $pkmStatus = 'belum';
            $pkmLabel = 'Belum Ikut PKM';
            $pkmMessage = $semester >= self::PKM_DEADLINE_SEMESTER
                ? 'Syarat wajib PKM (minimal lolos tahap Universitas untuk SKPI) belum tercatat. Segera koordinasi dengan DPA atau PKM Centre FTII.'
                : ($semester >= 5
                    ? 'Belum ada catatan PKM. Ikuti seleksi PKM di semester ganjil dan targetkan lolos minimal tahap Universitas untuk SKPI.'
                    : 'Wajib PKM dapat diikuti sejak semester 1 setiap semester ganjil. Minimal lolos tahap Universitas agar mendapat Sertifikat PKM (SKPI).');
        }

        // Ringkasan 4 Syarat Wajib SKPI FTII UHAMKA
        $itStatus = (string) ($setting?->skpi_it_status ?? StudentSetting::SKPI_BELUM);
        $itProvider = trim((string) ($setting?->skpi_it_provider ?? ''));
        $itDone = $itStatus === StudentSetting::SKPI_SELESAI;

        $bapsStatus = (string) ($setting?->skpi_baps_status ?? StudentSetting::SKPI_BELUM);
        $bapsDone = $bapsStatus === StudentSetting::SKPI_SELESAI;

        $pkmDone = $pkmStatus === 'selesai';

        $toeflStatus = (string) ($setting?->skpi_toefl_status ?? StudentSetting::SKPI_BELUM);
        $toeflScore = $setting?->skpi_toefl_score;
        $toeflDone = $toeflStatus === StudentSetting::SKPI_SELESAI;

        $skpiItems = [
            [
                'key' => 'kompetensi_it',
                'title' => 'Sertifikat Kompetensi IT',
                'done' => $itDone,
                'tone' => $itDone ? 'ok' : ($itStatus === StudentSetting::SKPI_PROSES ? 'warn' : 'neutral'),
                'status_label' => $itDone
                    ? 'Tuntas'.($itProvider !== '' ? ' ('.$itProvider.')' : '')
                    : ($itStatus === StudentSetting::SKPI_PROSES ? 'Proses'.($itProvider !== '' ? ' ('.$itProvider.')' : '') : 'Belum'),
                'note' => 'Wajib dari lembaga kredibel seperti Mahira atau BNSP.',
            ],
            [
                'key' => 'baps',
                'title' => 'Baitul Arqam Purna Studi (BAPS)',
                'done' => $bapsDone,
                'tone' => $bapsDone ? 'ok' : ($bapsStatus === StudentSetting::BAPS_TERDAFTAR ? 'warn' : 'neutral'),
                'status_label' => $bapsDone ? 'Selesai & Bersertifikat' : ($bapsStatus === StudentSetting::BAPS_TERDAFTAR ? 'Terdaftar' : 'Belum ikut'),
                'note' => 'Wajib mengikuti kegiatan Baitul Arqam Purna Studi.',
            ],
            [
                'key' => 'pkm',
                'title' => 'Sertifikat PKM',
                'done' => $pkmDone,
                'tone' => $pkmDone ? 'ok' : ($pkmStatus === 'proses' ? 'warn' : 'neutral'),
                'status_label' => $pkmLabel,
                'note' => 'Tiap semester ganjil (mulai Sem 1). Minimal lolos tahap Universitas agar diakui SKPI; lolos Fakultas saja belum sah SKPI.',
            ],
            [
                'key' => 'toefl',
                'title' => 'Sertifikat TOEFL UHAMKA',
                'done' => $toeflDone,
                'tone' => $toeflDone ? 'ok' : ($toeflStatus === StudentSetting::SKPI_PROSES ? 'warn' : 'neutral'),
                'status_label' => $toeflDone
                    ? 'Tuntas'.($toeflScore ? ' (Skor '.$toeflScore.')' : '')
                    : ($toeflStatus === StudentSetting::SKPI_PROSES ? 'Proses / Terdaftar' : 'Belum'),
                'note' => 'Sertifikat TOEFL wajib diterbitkan oleh UHAMKA.',
            ],
        ];
        $skpiCompleted = count(array_filter($skpiItems, fn ($i) => $i['done']));
        $skpiSummary = [
            'completed' => $skpiCompleted,
            'total' => 4,
            'pct' => (int) round(($skpiCompleted / 4) * 100),
            'all_done' => $skpiCompleted === 4,
            'pkm_stage' => $explicitStage,
            'items' => $skpiItems,
        ];

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
                $message = 'Kamu berada di fase cicil. Kumpulkan bukti kegiatan dan siapkan PKM (wajib maksimal semester 7) agar siap dikonversi mulai semester 5.';
            }
        } elseif ($semester <= 6) {
            $phaseLabel = 'Fase Konversi KRS (Semester 5–6)';
            $expected = $semester === 5 ? 10 : 14;
            if ($converted >= $target && $pkmStatus === 'selesai') {
                $riskLevel = 'rendah';
                $message = 'Target wajib 20 SKS MK Pengayaan dan syarat wajib PKM sudah tuntas!';
            } elseif ($effective >= $expected && $pkmStatus !== 'belum') {
                $riskLevel = 'rendah';
                $message = "Progresmu di jalur aman ({$converted} SKS terkonversi, {$ready} SKS siap rekognisi). Pastikan bukti siap rekognisi segera di-KRS-kan dengan Dosen Pengampu.";
            } else {
                $riskLevel = 'sedang';
                $parts = [];
                if ($effective < $expected) {
                    $parts[] = "progres baru {$effective} SKS (target minimal Sem {$semester}: {$expected} SKS)";
                }
                if ($pkmStatus === 'belum') {
                    $parts[] = 'wajib PKM (batas terakhir semester 7) belum tercatat';
                }
                $message = 'Perlu perhatian di semester '.$semester.': '.implode(' dan ', $parts).'.';
            }
        } else {
            $phaseLabel = 'Fase Kritis Kelulusan (Semester 7+)';
            if ($converted >= $target && $pkmStatus === 'selesai') {
                $riskLevel = 'rendah';
                $message = 'Selamat! Seluruh 20 SKS wajib MK Pengayaan dan syarat wajib PKM sudah tuntas.';
            } elseif ($pkmStatus === 'belum' || $effective < $target) {
                $riskLevel = 'tinggi';
                $issues = [];
                if ($effective < $target) {
                    $issues[] = "kekurangan {$remainingToCollect} SKS bukti pengayaan (baru {$converted} SKS terkonversi)";
                }
                if ($pkmStatus === 'belum') {
                    $issues[] = 'syarat wajib PKM (batas akhir semester 7) belum diikuti';
                }
                $message = "Perhatian kritis di semester {$semester}: ".implode(' dan ', $issues).'. Segera konsultasi dengan DPA dan Dosen Pengampu agar kelulusan tidak tertunda.';
            } else {
                $riskLevel = 'sedang';
                $message = "Bukti 20 SKS sudah terkumpul ({$converted} SKS terkonversi, {$ready} SKS siap rekognisi). Segera tuntaskan konversi KRS dan finalisasi bukti PKM sebelum UAS.";
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
            'pkm_status' => $pkmStatus,
            'pkm_label' => $pkmLabel,
            'pkm_message' => $pkmMessage,
            'skpi' => $skpiSummary,
        ];
    }
}