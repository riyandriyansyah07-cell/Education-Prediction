<?php

namespace App\Support;

/**
 * Validasi berkas data historis anonim untuk pelatihan model (UC-10).
 * Mendukung 10 variabel inti (maupun berkas 4 variabel dasar dengan pengisian turunan otomatis).
 */
final class HistoricalSheet
{
    public const MAX_ROWS = 5000;

    /** Kolom minimal wajib ada (6 variabel inti lainnya dapat diisi eksplisit atau diturunkan otomatis bila tidak disertakan). */
    private const REQUIRED = ['kehadiran', 'ips', 'rasio_sks_tidak_lulus', 'perubahan_ips', 'label'];

    private const ALIASES = [
        'attendance' => 'kehadiran',
        'min_attendance' => 'kehadiran_terendah',
        'low_attendance_courses' => 'mk_kehadiran_kurang',
        'jumlah_mk_bawah_batas_kehadiran' => 'mk_kehadiran_kurang',
        'attendance_delta' => 'tren_kehadiran',
        'perubahan_kehadiran' => 'tren_kehadiran',
        'failed_ratio' => 'rasio_sks_tidak_lulus',
        'rasio_gagal' => 'rasio_sks_tidak_lulus',
        'rasio_sks_bawah_c' => 'rasio_sks_tidak_lulus',
        'ips_delta' => 'perubahan_ips',
        'delta_ips' => 'perubahan_ips',
        'tren_ips' => 'perubahan_ips',
        'repeat_courses' => 'mk_mengulang',
        'jumlah_mk_mengulang' => 'mk_mengulang',
        'credits_delta' => 'perubahan_sks',
        'beban_sks_delta' => 'perubahan_sks',
    ];

    private const IDENTITY = ['nim', 'nama', 'name', 'email', 'username', 'ref'];

    /**
     * @param  list<list<string|null>>  $rows
     * @return array{records:list<array>,errors:list<array{row:int,message:string}>,label_counts:array<string,int>}
     */
    public static function validate(array $rows): array
    {
        $fail = fn (string $m) => ['records' => [], 'errors' => [['row' => 1, 'message' => $m]], 'label_counts' => []];

        if ($rows === []) {
            return $fail('Berkas kosong.');
        }
        $header = array_map(function ($h) {
            $h = trim(strtolower((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)));
            $h = trim((string) preg_replace('/[^a-z0-9]+/', '_', $h), '_');

            return self::ALIASES[$h] ?? $h;
        }, array_shift($rows));

        $identity = array_values(array_intersect($header, self::IDENTITY));
        if ($identity !== []) {
            return $fail('Berkas memuat kolom identitas ('.implode(', ', $identity).'). Hapus kolom itu. Data historis harus anonim.');
        }
        $missing = array_values(array_diff(self::REQUIRED, $header));
        if ($missing !== []) {
            return $fail('Kolom wajib tidak ditemukan: '.implode(', ', $missing).'. Pakai templat yang disediakan.');
        }
        if (count($rows) > self::MAX_ROWS) {
            return $fail('Berkas berisi lebih dari '.self::MAX_ROWS.' baris.');
        }
        $col = array_flip($header);

        $records = [];
        $errors = [];
        $counts = array_fill_keys(RiskModel::LEVELS, 0);

        foreach ($rows as $i => $cells) {
            $line = $i + 2;
            if (trim(implode('', array_map('strval', $cells))) === '') {
                continue;
            }

            $getNum = function (string $k) use ($cells, $col): ?float {
                if (! isset($col[$k])) {
                    return null;
                }
                $raw = trim((string) ($cells[$col[$k]] ?? ''));
                if ($raw === '') {
                    return null;
                }

                return Grades::parseNumber($raw);
            };

            $attendance = $getNum('kehadiran');
            $ips = $getNum('ips');
            $ratio = $getNum('rasio_sks_tidak_lulus');
            $delta = $getNum('perubahan_ips');
            $label = strtolower(trim((string) ($cells[$col['label']] ?? '')));

            // 6 variabel inti tambahan (bila kolom ada di CSV/XLSX, validasi; bila tidak ada, turunkan secara konsisten)
            $minAtt = $getNum('kehadiran_terendah');
            $lowAttCourses = $getNum('mk_kehadiran_kurang');
            $attDelta = $getNum('tren_kehadiran');
            $ipk = $getNum('ipk');
            $repeatCourses = $getNum('mk_mengulang');
            $creditsDelta = $getNum('perubahan_sks');

            $rowErrors = [];
            if ($attendance === null || $attendance < 0.0 || $attendance > 100.0) {
                $rowErrors[] = 'Kehadiran harus 0 sampai 100.';
            }
            if ($minAtt !== null && ($minAtt < 0.0 || $minAtt > 100.0)) {
                $rowErrors[] = 'Kehadiran terendah harus 0 sampai 100.';
            }
            if ($lowAttCourses !== null && ($lowAttCourses < 0 || $lowAttCourses > 20)) {
                $rowErrors[] = 'Jumlah mata kuliah di bawah batas kehadiran harus 0 sampai 20.';
            }
            if ($attDelta !== null && ($attDelta < -100.0 || $attDelta > 100.0)) {
                $rowErrors[] = 'Tren kehadiran harus antara -100 dan 100.';
            }
            if ($ips === null || $ips < 0.0 || $ips > 4.0) {
                $rowErrors[] = 'IPS harus 0 sampai 4.';
            }
            if ($ipk !== null && ($ipk < 0.0 || $ipk > 4.0)) {
                $rowErrors[] = 'IPK harus 0 sampai 4.';
            }
            if ($ratio === null || $ratio < 0.0 || $ratio > 1.0) {
                $rowErrors[] = 'Rasio SKS bernilai di bawah C harus 0 sampai 1.';
            }
            if ($delta === null || $delta < -4.0 || $delta > 4.0) {
                $rowErrors[] = 'Perubahan IPS harus antara -4 dan 4.';
            }
            if ($repeatCourses !== null && ($repeatCourses < 0 || $repeatCourses > 20)) {
                $rowErrors[] = 'Jumlah mata kuliah mengulang harus 0 sampai 20.';
            }
            if ($creditsDelta !== null && ($creditsDelta < -24 || $creditsDelta > 24)) {
                $rowErrors[] = 'Perubahan beban SKS harus antara -24 dan 24.';
            }
            if (! in_array($label, RiskModel::LEVELS, true)) {
                $rowErrors[] = 'Label harus rendah, sedang, atau tinggi.';
            }

            if ($rowErrors) {
                foreach ($rowErrors as $m) {
                    $errors[] = ['row' => $line, 'message' => $m];
                }
                if (count($errors) >= 50) {
                    break;
                }

                continue;
            }

            $records[] = [
                'attendance' => $attendance,
                'min_attendance' => $minAtt ?? round(max(0.0, $attendance - 5.0), 2),
                'low_attendance_courses' => (int) ($lowAttCourses ?? ($attendance < RiskModel::MIN_ATTENDANCE_LIMIT ? 1 : 0)),
                'attendance_delta' => $attDelta ?? 0.0,
                'ips' => $ips,
                'ipk' => $ipk ?? round(min(4.0, max(0.0, $ips - ($delta * 0.3))), 2),
                'ips_delta' => $delta,
                'failed_ratio' => $ratio,
                'repeat_courses' => (int) ($repeatCourses ?? ($ratio >= 0.25 ? 2 : ($ratio > 0 ? 1 : 0))),
                'credits_delta' => (int) ($creditsDelta ?? 0),
                'label' => $label,
            ];
            $counts[$label]++;
        }

        if ($errors) {
            return ['records' => [], 'errors' => $errors, 'label_counts' => []];
        }
        if (count($records) < 100) {
            return $fail('Data historis minimal 100 baris agar ada data latih dan data uji. Berkasmu berisi '.count($records).'.');
        }

        return ['records' => $records, 'errors' => [], 'label_counts' => $counts];
    }
}
