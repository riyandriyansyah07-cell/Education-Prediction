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
        return view('student.import', [
            'batches' => ImportBatch::where('student_ref', $request->user()->student_ref)->latest('id')->limit(5)->get(),
            'maxKb' => config('risk.import_max_kb'),
        ]);
    }

    public function store(Request $request, ImportService $import): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:'.config('risk.import_max_kb')],
        ], [
            'file.required' => 'Pilih berkas CSV, XLSX, atau PDF dulu.',
            'file.max' => 'Ukuran berkas maksimal 2 MB.',
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

    /** Fitur Konversi PDF KHS langsung ke format template Excel/CSV menggunakan parser tunggal di ImportService. */
    public function convertPdfToExcel(Request $request, ImportService $import): BinaryFileResponse|StreamedResponse|RedirectResponse
    {
        $request->validate([
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:2048'],
        ], [
            'pdf_file.required' => 'Pilih file PDF KHS terlebih dahulu.',
            'pdf_file.mimes' => 'Format file harus berupa PDF.',
            'pdf_file.max' => 'Ukuran file PDF maksimal 2 MB.',
        ]);

        $file = $request->file('pdf_file');

        try {
            $rows = $import->parsePdfTranscript($file->getRealPath(), $request->user()->username);
        } catch (\Throwable $e) {
            return back()->withErrors(['pdf_file' => 'Gagal membaca PDF: '.$e->getMessage()]);
        }

        if (empty($rows) || count($rows) <= 1) {
            return back()->withErrors(['pdf_file' => 'Tidak dapat mengenali baris mata kuliah dari file PDF tersebut.']);
        }

        $format = $request->query('format', 'xlsx');

        if ($format === 'xlsx') {
            $tmpFile = tempnam(sys_get_temp_dir(), 'conv_').'.xlsx';
            $writer = new XlsxWriter;
            $writer->openToFile($tmpFile);
            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }
            $writer->close();

            return response()->download($tmpFile, 'templat-hasil-konversi-khs.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        $csv = "\xEF\xBB\xBF".implode("\n", array_map(fn ($r) => implode(',', $r), $rows))."\n";

        return response()->streamDownload(fn () => print ($csv), 'templat-hasil-konversi-khs.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function template(Request $request): StreamedResponse|BinaryFileResponse
    {
        $rows = [
            ['semester', 'kode_mk', 'nama_mk', 'sks', 'nilai', 'kehadiran_persen', 'nilai_uts', 'nilai_tugas', 'nilai_uas'],
            [1, 'IF101', 'Algoritma dan Pemrograman', 3, 'A', 95, '', '', ''],
            [6, 'IF601', 'Pemrograman Mobile', 3, '', 90, 60, 70, ''],
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

        $csv = "\xEF\xBB\xBF".implode("\n", array_map(fn ($r) => implode(',', $r), $rows))."\n";

        return response()->streamDownload(fn () => print ($csv), 'templat-data-akademik.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}