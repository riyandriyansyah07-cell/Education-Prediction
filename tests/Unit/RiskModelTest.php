<?php

namespace Tests\Unit;

use App\Support\Grades;
use App\Support\HistoricalSheet;
use App\Support\RiskModel;
use App\Support\SheetValidator;
use App\Support\ThresholdTrainer;
use PHPUnit\Framework\TestCase;

class RiskModelTest extends TestCase
{
    public function test_skor_dan_tingkat_risiko_serta_proyeksi_u_min(): void
    {
        $tinggi = ['attendance' => 68, 'ips' => 1.85, 'ipk' => 1.90, 'failed_ratio' => 0.35, 'repeat_courses' => 2, 'ips_delta' => -0.55];
        $aman = ['attendance' => 92, 'ips' => 3.4, 'ipk' => 3.35, 'failed_ratio' => 0, 'repeat_courses' => 0, 'ips_delta' => 0.1];

        $this->assertGreaterThanOrEqual(5.0, RiskModel::score($tinggi));
        $this->assertSame('tinggi', RiskModel::classify(RiskModel::score($tinggi), RiskModel::DEFAULT_THRESHOLDS));
        $this->assertSame('rendah', RiskModel::classify(RiskModel::score($aman), RiskModel::DEFAULT_THRESHOLDS));
        $this->assertSame([], RiskModel::factors($aman));

        // Contoh di dokumen rancangan: UTS 60, Tugas 70, Kehadiran 90 -> poin pra-UAS 41,5 -> U_min(C=56) = 36,25 & U_min(B=68) = 66,25
        $proj = RiskModel::projectCourse([
            'credits' => 3,
            'attendance' => 90,
            'uts_score' => 60,
            'assignment_score' => 70,
            'uas_score' => null,
            'is_official_khs' => false,
        ]);
        $this->assertEqualsWithDelta(36.25, $proj['min_uas_for_c'], 0.01);
        $this->assertEqualsWithDelta(66.25, $proj['min_uas_for_b'], 0.01);
        $this->assertSame('rendah', $proj['course_risk_level']);
    }

    public function test_konversi_nilai_skala_uhamka(): void
    {
        $this->assertSame(4.0, Grades::toPoint('A'));
        $this->assertSame(4.0, Grades::toPoint('80'));
        $this->assertSame(3.0, Grades::toPoint('68'));
        $this->assertSame(2.0, Grades::toPoint('56'));
        $this->assertSame(1.0, Grades::toPoint('45'));
        $this->assertSame(0.0, Grades::toPoint('44'));
        $this->assertSame(3.0, Grades::toPoint('3', 'bobot'));
        $this->assertNull(Grades::toPoint('Z'));
    }

    public function test_validator_menolak_baris_salah_dan_menerima_kolom_opsional_tugas(): void
    {
        $header = ['semester', 'kode_mk', 'nama_mk', 'sks', 'nilai', 'kehadiran_persen', 'nilai_uts', 'nilai_tugas', 'sumber_tugas'];

        $bad = SheetValidator::validate([$header, ['x', 'IF1', 'Basis Data', '3', 'Q', '150', '', '', '']], '2303045009');
        $this->assertNotEmpty($bad['errors']);
        $this->assertSame([], $bad['records']);

        $ok = SheetValidator::validate([$header, ['6', 'IF601', '=cmd|calc', '3', '', '90', '60', '70', 'olu']], '2303045009');
        $this->assertSame([], $ok['errors']);
        $this->assertSame("'=cmd|calc", $ok['records'][0]['course_name']);
        $this->assertFalse($ok['records'][0]['is_official_khs']);
        $this->assertSame('olu', $ok['records'][0]['assignment_source']);
        $this->assertEqualsWithDelta(36.25, $ok['records'][0]['min_uas_for_c'], 0.01);

        // C1: Bobot numerik 0-4.0 di kolom "nilai" (skala 0-100) harus meminta konfirmasi (error), bukan diam-diam jadi E
        $ambiguousBobot = SheetValidator::validate([$header, ['5', 'IF501', 'Big Data', '3', '3.5', '90', '', '', '']], '2303045009');
        $this->assertNotEmpty($ambiguousBobot['errors']);
        $this->assertStringContainsString('nilai_bobot', $ambiguousBobot['errors'][0]['message']);

        // C2: Apostrof di awal sel di-strip saat neutralize()
        $this->assertSame('IF601', SheetValidator::neutralize("'IF601"));
        $this->assertSame("'=cmd|calc", SheetValidator::neutralize("'=cmd|calc"));
    }

    public function test_pelatihan_ambang_memakai_data_uji_terpisah(): void
    {
        mt_srand(7);
        $rows = [];
        for ($i = 0; $i < 200; $i++) {
            $label = ['rendah', 'sedang', 'tinggi'][$i % 3];
            $base = ['rendah' => [92, 3.4, 0.0, 0.1], 'sedang' => [79, 2.5, 0.12, -0.2], 'tinggi' => [64, 1.75, 0.40, -0.6]][$label];
            $rows[] = [
                'attendance' => $base[0] + mt_rand(-2, 2),
                'ips' => $base[1] + mt_rand(-10, 10) / 100,
                'ipk' => $base[1],
                'failed_ratio' => $base[2],
                'ips_delta' => $base[3],
                'label' => $label,
            ];
        }
        $fit = ThresholdTrainer::fit($rows);

        $this->assertSame(140, $fit['train_rows']);
        $this->assertSame(60, $fit['test_rows']);
        $this->assertGreaterThan(0.7, $fit['metrics']['macro_f1']);
    }

    public function test_explain_memakai_poin_luar_untuk_faktor_ml(): void
    {
        $f = ['attendance' => 90.0, 'ips' => 2.2, 'failed_ratio' => 0.2, 'ips_delta' => 0.3];
        $out = RiskModel::explain($f, ['attendance' => 0.4, 'ips' => 1.6, 'failed' => -0.3, 'trend' => 0.2]);

        $this->assertSame(['ips', 'attendance', 'ips_delta'], array_column($out, 'key'));
        $this->assertSame(1.6, $out[0]['points']);
        $this->assertEqualsWithDelta(100.0, array_sum(array_column($out, 'share')), 0.2);
        $this->assertSame([], RiskModel::explain($f, ['attendance' => -1, 'ips' => 0.004]));
    }

    public function test_pembagian_data_latih_uji_dan_data_terlalu_sedikit(): void
    {
        $rows = array_map(fn ($i) => ['attendance' => 80, 'ips' => 3, 'failed_ratio' => 0, 'ips_delta' => 0, 'label' => 'rendah', 'i' => $i], range(0, 99));
        [$train, $test] = ThresholdTrainer::split($rows);

        $this->assertCount(70, $train);
        $this->assertCount(30, $test);

        // C3: Pastikan data di-shuffle secara deterministik dan tetap saling lepas
        $trainIds = array_column($train, 'i');
        $testIds = array_column($test, 'i');
        $this->assertEmpty(array_intersect($trainIds, $testIds));
        $merged = array_merge($trainIds, $testIds);
        sort($merged);
        $this->assertSame(range(0, 99), $merged);
        $this->assertNotSame([7, 8, 9], array_slice($testIds, 0, 3));

        [$train2, $test2] = ThresholdTrainer::split($rows);
        $this->assertSame($testIds, array_column($test2, 'i'));

        $this->expectException(\InvalidArgumentException::class);
        ThresholdTrainer::split(array_slice($rows, 0, 20));
    }

    public function test_data_historis_menolak_kolom_identitas_dan_label_salah(): void
    {
        $header = ['kehadiran', 'ips', 'rasio_sks_tidak_lulus', 'perubahan_ips', 'label'];

        $withId = HistoricalSheet::validate([array_merge($header, ['nim']), ['90', '3', '0', '0', 'rendah', '2303045009']]);
        $this->assertStringContainsString('identitas', $withId['errors'][0]['message']);

        $rows = [$header];
        for ($i = 0; $i < 100; $i++) {
            $rows[] = ['85', '3.1', '0', '-0.2', ['rendah', 'sedang', 'tinggi'][$i % 3]];
        }
        $ok = HistoricalSheet::validate($rows);
        $this->assertSame([], $ok['errors']);
        $this->assertSame(-0.2, $ok['records'][0]['ips_delta']);
        $this->assertArrayHasKey('min_attendance', $ok['records'][0]);
        $this->assertArrayHasKey('ipk', $ok['records'][0]);

        $rows[5][4] = 'kritis';
        $this->assertNotEmpty(HistoricalSheet::validate($rows)['errors']);
    }
}
