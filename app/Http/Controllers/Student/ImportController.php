<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Services\ImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function show(Request $request): View
    {
        $maxKb = (int) config('risk.import_max_kb', 2048);

        return view('student.import', [
            'batches' => ImportBatch::where('student_ref', $request->user()->student_ref)->latest('id')->limit(5)->get(),
            'maxKb' => $maxKb,
            'maxLabel' => self::formatMaxSize($maxKb),
        ]);
    }

    public function store(Request $request, ImportService $import): RedirectResponse
    {
        $maxKb = (int) config('risk.import_max_kb', 2048);
        $maxLabel = self::formatMaxSize($maxKb);

        $request->validate([
            'file' => ['required', 'file', 'max:'.$maxKb],
        ], [
            'file.required' => 'Pilih berkas CSV, XLSX, atau PDF dulu.',
            'file.max' => "Ukuran berkas maksimal {$maxLabel} ({$maxKb} KB).",
        ]);

        $result = $import->handle($request->user(), $request->file('file'));

        if (! $result['ok']) {
            return redirect()->route('mhs.impor')->with('import_errors', $result['errors']);
        }

        return redirect()->route('mhs.ringkasan')->with(
            'status',
            'Impor berhasil: '.$result['rows'].' mata kuliah dari semester '.implode(', ', $result['semesters']).'. Risiko sudah dihitung.'
        );
    }

    public function template(Request $request): StreamedResponse|BinaryFileResponse
    {
        $rows = [
            ['semester', 'kode_mk', 'nama_mk', 'sks', 'nilai', 'kehadiran_persen', 'nilai_keaktifan', 'nilai_tugas', 'nilai_uts', 'nilai_uas'],
            [1, 'IF101', 'Algoritma dan Pemrograman', 3, 'A', 95, 85, 95, 87, 55],
            [6, 'IF601', 'Pemrograman Mobile', 3, '', 90, 85, 70, 60, ''],
        ];

        if ($request->query('format') === 'xlsx') {
            $tmpFile = tempnam(sys_get_temp_dir(), 'tpl_mhs_').'.xlsx';
            $writer = new XlsxWriter;
            $writer->openToFile($tmpFile);
            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }
            $writer->close();

            return response()->download($tmpFile, 'templat-data-akademik.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        $csv = self::encodeCsv($rows);

        return response()->streamDownload(fn () => print ($csv), 'templat-data-akademik.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Bangun string CSV ber-BOM UTF-8 dengan escaping RFC-4180 melalui fputcsv().
     *
     * @param  list<list<scalar|null>>  $rows
     */
    private static function encodeCsv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }

    private static function formatMaxSize(int $maxKb): string
    {
        return $maxKb >= 1024 && $maxKb % 1024 === 0
            ? ($maxKb / 1024).' MB'
            : $maxKb.' KB';
    }
}