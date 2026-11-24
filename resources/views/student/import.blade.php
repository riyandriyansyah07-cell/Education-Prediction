@extends('layouts.app')

@section('title', 'Impor data')

@section('content')
<h1 class="rise text-3xl sm:text-4xl">Impor data akademik</h1>
<p class="rise mt-1 text-ink-2" style="--i: 1">Unggah CSV, XLSX, atau PDF KHS. Berkas mentah tidak disimpan.</p>

<div class="mt-8">@include('partials.import-errors')</div>

<div class="mt-8 grid gap-10 lg:grid-cols-12">
    <section class="lg:col-span-6">
        <div class="rounded-md border border-line bg-surface p-6">
            <h2 class="text-lg font-medium">Unggah berkas</h2>
            <form method="POST" action="{{ route('mhs.impor.store') }}" enctype="multipart/form-data" class="mt-4">
                @csrf
                <div data-dropzone class="rounded-md border border-dashed border-line-strong bg-bg p-6 transition-colors data-[over]:border-accent data-[over]:bg-accent-soft">
                    <label for="file" class="label">Pilih file CSV, TXT, XLSX, atau PDF</label>
                    <input id="file" name="file" type="file" accept=".csv,.txt,.xlsx,.pdf" required
                        class="block w-full text-sm file:mr-4 file:h-9 file:cursor-pointer file:rounded-md file:border file:border-line-strong file:bg-surface file:px-3 file:text-ink hover:file:bg-sunken">
                    @error('file')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <p class="mt-3 text-xs text-ink-3">
                    Maks. {{ number_format($maxKb / 1024, 0) }} MB &middot; Maks. 500 baris &middot; Menimpa data semester yang sama
                </p>

                <div class="mt-5 flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary"><x-icon name="file-arrow-up" /> Impor &amp; Hitung</button>
                    <a href="{{ route('mhs.templat', ['format' => 'xlsx']) }}" class="btn btn-quiet btn-sm"><x-icon name="download-simple" /> Templat XLSX</a>
                    <a href="{{ route('mhs.templat', ['format' => 'csv']) }}" class="btn btn-quiet btn-sm"><x-icon name="download-simple" /> Templat CSV</a>
                </div>
            </form>
        </div>
    </section>

    <section class="lg:col-span-6">
        <div class="rounded-md border border-line bg-surface p-6">
            <h2 class="text-lg font-medium">Format kolom</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="tbl w-full text-xs">
                    <thead><tr><th>Kolom</th><th>Format</th><th>Contoh</th></tr></thead>
                    <tbody>
                        <tr><td class="mono">semester</td><td>1–14</td><td class="mono">6</td></tr>
                        <tr><td class="mono">kode_mk</td><td>Kode MK</td><td class="mono">IF601</td></tr>
                        <tr><td class="mono">nama_mk</td><td>Nama MK</td><td>Basis Data</td></tr>
                        <tr><td class="mono">sks</td><td>1–6</td><td class="mono">3</td></tr>
                        <tr><td class="mono">nilai</td><td>A–E / 0–100 (opsional)</td><td class="mono">B / 72</td></tr>
                        <tr><td class="mono">kehadiran_persen</td><td>0–100 (min. 75%)</td><td class="mono">86</td></tr>
                        <tr><td class="mono">nilai_uts / tugas / uas</td><td>Opsional (0–100)</td><td class="mono">65 / 75 / -</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

@if ($batches->isNotEmpty())
    <section class="mt-10 border-t border-line pt-8">
        <h2 class="text-lg font-medium">Impor terakhir</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="tbl min-w-[32rem]">
                <thead><tr><th>Waktu</th><th>Berkas</th><th>Baris</th><th>Semester</th></tr></thead>
                <tbody>
                    @foreach ($batches as $b)
                        <tr>
                            <td class="num">{{ $b->created_at->translatedFormat('j M Y, H:i') }}</td>
                            <td class="mono break-all">{{ $b->source_name }}</td>
                            <td class="num">{{ $b->rows_valid }}</td>
                            <td class="num">{{ implode(', ', $b->semesters) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
@endsection