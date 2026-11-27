<?php

namespace App\Services;

use App\Models\AcademicRecord;
use App\Models\ImportBatch;
use App\Models\User;
use App\Support\EnrichmentCatalog;
use App\Support\Grades;
use App\Support\SheetValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Smalot\PdfParser\Parser;
use Throwable;

class ImportService
{
    public function __construct(private RiskService $risk) {}

    public function handle(User $student, UploadedFile $file): array
    {
        $fail = fn (string $message) => ['ok' => false, 'errors' => [['row' => 1, 'message' => $message]], 'rows' => 0, 'semesters' => [], 'result' => null];

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['csv', 'txt', 'xlsx', 'pdf'], true)) {
            return $fail('Format berkas harus CSV, XLSX, atau PDF.');
        }

        try {
            if ($extension === 'pdf') {
                $rows = $this->parsePdfTranscript($file->getRealPath(), $student->username);
            } else {
                $rows = SheetReader::read($file->getRealPath(), $extension);
            }
        } catch (Throwable $e) {
            return $fail('Gagal memproses berkas: '.$e->getMessage());
        }

        if ($extension === 'pdf' && (empty($rows) || count($rows) <= 1)) {
            return $fail('Berkas PDF tidak berisi baris data mata kuliah yang dapat dikenali.');
        }

        $checked = SheetValidator::validate($rows, $student->username);
        if ($checked['errors'] !== []) {
            return ['ok' => false, 'errors' => $checked['errors'], 'rows' => 0, 'semesters' => [], 'result' => null];
        }

        $ref = $student->student_ref;
        DB::transaction(function () use ($checked, $ref, $file) {
            $batch = ImportBatch::create([
                'student_ref' => $ref,
                'source_name' => mb_substr($file->getClientOriginalName(), 0, 120),
                'rows_valid' => count($checked['records']),
                'semesters' => $checked['semesters'],
            ]);

            AcademicRecord::where('student_ref', $ref)->whereIn('semester', $checked['semesters'])->delete();

            $now = now();
            foreach (array_chunk($checked['records'], 200) as $chunk) {
                AcademicRecord::insert(array_map(fn ($r) => $r + [
                    'student_ref' => $ref,
                    'import_batch_id' => $batch->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk));
            }
        });

        return [
            'ok' => true,
            'errors' => [],
            'rows' => count($checked['records']),
            'semesters' => $checked['semesters'],
            'result' => $this->risk->refresh($ref),
        ];
    }

    /**
     * Ekstrak seluruh baris mata kuliah secara dinamis dari berkas PDF KHS UHAMKA
     * (berapapun jumlah mata kuliah per semester), termasuk komponen nilai Tugas, UTS, UAS, dan %Hadir jika terisi.
     */
    public function parsePdfTranscript(string $filePath, string $studentUsername = '2303045009'): array
    {
        $header = @file_get_contents($filePath, false, null, 0, 5);
        if ($header !== '%PDF-') {
            throw new \Exception('Berkas yang diunggah bukan merupakan file PDF resmi yang valid.');
        }

        $parser = new Parser;
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        $lines = explode("\n", $text);

        $rows = [
            ['semester', 'kode_mk', 'nama_mk', 'sks', 'nilai', 'kehadiran_persen', 'nilai_keaktifan', 'nilai_tugas', 'nilai_uts', 'nilai_uas'],
        ];

        $entryYear = 2000 + (int) substr(preg_replace('/[^0-9]/', '', $studentUsername), 0, 2);
        if ($entryYear < 2015 || $entryYear > 2035) {
            $entryYear = 2023;
        }

        $academicYearStart = $entryYear;
        $termType = 'gasal';
        $explicitSemester = null;

        foreach ($lines as $line) {
            $cleanLine = trim((string) preg_replace('/\s+/', ' ', $line));

            if (preg_match('/semester\s*[:\-]?\s*(\d+)/i', $cleanLine, $numSem)) {
                $explicitSemester = (int) $numSem[1];
            }

            if (preg_match('/(?:th|tahun)?\s*akademik.*?(\d{4})/i', $cleanLine, $yrMatch)) {
                $academicYearStart = (int) $yrMatch[1];
            } elseif (preg_match('/(\d{4})\s*\/\s*\d{4}/', $cleanLine, $yrSlash)) {
                $academicYearStart = (int) $yrSlash[1];
            }

            if (preg_match('/(?:semester|periode)?\s*[:\-]?\s*(gasal|ganjil|genap|odd|even)/i', $cleanLine, $termMatch)) {
                $val = strtolower(trim($termMatch[1]));
                if (in_array($val, ['gasal', 'ganjil', 'odd'], true)) {
                    $termType = 'gasal';
                } elseif (in_array($val, ['genap', 'even'], true)) {
                    $termType = 'genap';
                }
            } elseif (preg_match('/^[:\-]?\s*(gasal|ganjil|genap)\b/i', $cleanLine, $kwMatch)) {
                $termType = strtolower($kwMatch[1]);
            }
        }

        if ($explicitSemester !== null && $explicitSemester >= 1 && $explicitSemester <= 14) {
            $currentSemester = $explicitSemester;
        } else {
            $diffYears = max(0, $academicYearStart - $entryYear);
            $isGasal = in_array($termType, ['gasal', 'ganjil', 'odd'], true);
            $currentSemester = ($diffYears * 2) + ($isGasal ? 1 : 2);
            $currentSemester = max(1, min(14, $currentSemester));
        }

        $validLetters = ['A', 'A-', 'AB', 'B+', 'B', 'B-', 'BC', 'C+', 'C', 'C-', 'D+', 'D', 'E'];

        foreach ($lines as $line) {
            $line = trim((string) preg_replace('/\s+/', ' ', $line));
            if ($line === '') {
                continue;
            }

            if (preg_match('/^\d+\s+([1-6])\s+([A-E][\+\-]?|AB|BC)?\s*([0-9A-Z\-]{5,12})\s+(.+?)\s+([\d\.,]+\s+[\d\.,]+.*)$/ui', $line, $m)) {
                $sks = trim($m[1]);
                $explicitGrade = strtoupper(trim((string) $m[2]));
                $code = trim($m[3]);
                $rawName = trim($m[4]);
                $tail = trim($m[5]);

                if (in_array(strtoupper($code), ['KODE', 'KODEMK', 'MATA', 'MATAKULIAH'], true)) {
                    continue;
                }

                $namaMk = trim((string) preg_replace('/^\d+[\.\-\s]*/', '', $rawName));
                if ($namaMk === '') {
                    $namaMk = $rawName;
                }

                // Ekstrak seluruh token angka pada bagian nilai di belakang nama mata kuliah
                preg_match_all('/\d+(?:[.,]\d+)?/', $tail, $tokMatches);
                $tokens = $tokMatches[0] ?? [];

                // Pada KHS UHAMKA, Rata2 selalu berformat desimal titik (mis. 81.85 / 70.00),
                // sedangkan %Hadir berformat desimal koma (mis. 93,00 / 100,00) setelah Rata2 (atau kosong).
                $rataIdx = null;
                foreach ($tokens as $idx => $tok) {
                    if (preg_match('/^\d+\.\d{2}$/', $tok)) {
                        $rataIdx = $idx;
                    }
                }

                $rataVal = null;
                $hadirVal = null;
                $componentScores = [];

                if ($rataIdx !== null) {
                    $rataVal = (float) $tokens[$rataIdx];
                    $before = array_slice($tokens, 0, $rataIdx);
                    $after = array_slice($tokens, $rataIdx + 1);

                    // Ambil %Hadir bila tersedia setelah Rata2 (format koma, mis. 93,00 atau 100,00)
                    if ($after !== []) {
                        $cand = (float) str_replace(',', '.', (string) end($after));
                        if ($cand >= 0.0 && $cand <= 100.0) {
                            $hadirVal = $cand;
                        }
                    }

                    // Fallback bila ada extractor PDF yang meletakkan %Hadir (koma) di depan Keaktifan
                    if ($before !== [] && preg_match('/^\d+,\d{2}$/', $before[0])) {
                        $cand = (float) str_replace(',', '.', $before[0]);
                        if ($hadirVal === null && $cand >= 0.0 && $cand <= 100.0) {
                            $hadirVal = $cand;
                        }
                        array_shift($before);
                    }

                    foreach ($before as $rawScore) {
                        $val = (float) str_replace(',', '.', $rawScore);
                        if ($val >= 0.0 && $val <= 100.0) {
                            $componentScores[] = $val;
                        }
                    }
                } else {
                    // Fallback untuk format PDF lain yang tidak memakai titik 2 desimal pada Rata2
                    if (preg_match_all('/(\d{2,3},\d{2})/', $tail, $commaMatches)) {
                        $cand = (float) str_replace(',', '.', (string) end($commaMatches[1]));
                        if ($cand >= 0.0 && $cand <= 100.0) {
                            $hadirVal = $cand;
                        }
                    }
                }

                // Petakan komponen [Keaktifan, Tugas, UTS, UAS] secara dinamis sesuai jumlah kolom yang terisi
                $nilaiKeaktifan = '';
                $nilaiTugas = '';
                $nilaiUts = '';
                $nilaiUas = '';
                $countScores = count($componentScores);
                if ($countScores >= 4) {
                    // Urutan lengkap KHS UHAMKA: Keaktifan, Tugas, UTS, UAS
                    $nilaiKeaktifan = $this->formatScore($componentScores[0]);
                    $nilaiTugas = $this->formatScore($componentScores[1]);
                    $nilaiUts = $this->formatScore($componentScores[2]);
                    $nilaiUas = $this->formatScore($componentScores[3]);
                } elseif ($countScores === 3) {
                    // Jika Keaktifan kosong: Tugas, UTS, UAS
                    $nilaiTugas = $this->formatScore($componentScores[0]);
                    $nilaiUts = $this->formatScore($componentScores[1]);
                    $nilaiUas = $this->formatScore($componentScores[2]);
                } elseif ($countScores === 2) {
                    // Jika hanya 2 komponen: UTS, UAS
                    $nilaiUts = $this->formatScore($componentScores[0]);
                    $nilaiUas = $this->formatScore($componentScores[1]);
                } elseif ($countScores === 1) {
                    // Kasus MK Pengayaan yang hanya diisi nilai UAS saja (mis. Pengembangan Profesi)
                    $nilaiUas = $this->formatScore($componentScores[0]);
                }

                // Tentukan nilai huruf akhir
                $nilaiHuruf = 'B';
                if ($explicitGrade !== '' && in_array($explicitGrade, $validLetters, true)) {
                    $nilaiHuruf = $explicitGrade;
                } elseif ($rataVal !== null) {
                    $nilaiHuruf = Grades::toLetter($rataVal <= 4.0 ? $rataVal * 25 : $rataVal);
                } elseif ($tokens !== []) {
                    $lastNum = (float) str_replace(',', '.', (string) end($tokens));
                    $nilaiHuruf = Grades::toLetter($lastNum <= 4.0 ? $lastNum * 25 : $lastNum);
                }

                // Bila %Hadir kosong di KHS (umum pada 19 MK Pengayaan non-kelas), jangan ambil Rata2 sebagai kehadiran
                if ($hadirVal !== null) {
                    $kehadiran = $this->formatScore($hadirVal);
                } else {
                    $enrichment = EnrichmentCatalog::matchCourseName($namaMk);
                    $kehadiran = ($enrichment && ! $enrichment['is_class_based']) ? '100' : '85';
                }

                $rows[] = [
                    (string) $currentSemester,
                    $code,
                    $namaMk,
                    $sks,
                    $nilaiHuruf,
                    $kehadiran,
                    $nilaiKeaktifan,
                    $nilaiTugas,
                    $nilaiUts,
                    $nilaiUas,
                ];
            }
        }

        return $rows;
    }

    private function formatScore(float $val): string
    {
        return fmod($val, 1.0) === 0.0 ? (string) (int) $val : (string) round($val, 2);
    }
}