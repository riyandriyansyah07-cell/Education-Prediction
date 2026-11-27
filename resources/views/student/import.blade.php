@extends('layouts.app')

@section('title', 'Impor data')

@section('content')
<style>
    .imp-dropzone {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        border-radius: 16px;
        border: 1.5px dashed color-mix(in srgb, var(--accent) 34%, var(--line-strong));
        background: var(--sunken);
        padding: 2rem 1.25rem;
        cursor: pointer;
        transition: border-color 150ms ease, background-color 150ms ease;
    }
    .imp-dropzone:hover {
        border-color: var(--accent);
        background: color-mix(in srgb, var(--accent) 5%, var(--sunken));
    }
    .imp-drop-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: var(--surface);
        border: 1px solid var(--line);
        color: var(--accent);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.75rem;
        box-shadow: var(--shadow-card);
    }
    .imp-file-input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }
    .imp-file-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin-top: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        background: var(--accent-soft);
        color: var(--accent);
        font-size: 0.75rem;
        font-weight: 600;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>

<div>
    <h1 class="rise">Impor data akademik</h1>
    <p class="rise mt-1.5 max-w-[60ch] text-sm text-ink-2" style="--i: 1">Unggah CSV, XLSX, atau PDF KHS. Berkas mentah tidak disimpan di server.</p>
</div>

<div class="mt-6">@include('partials.import-errors')</div>

<div class="mt-6 grid gap-6 lg:grid-cols-12" style="align-items:start;">
    <section class="lg:col-span-6">
        <div class="bg-surface" style="padding:1.5rem 1.6rem;">
            <h2 class="text-base font-semibold text-ink">Unggah berkas KHS / nilai</h2>
            <p class="mt-1 text-xs text-ink-3">Maks. {{ $maxLabel ?? (number_format($maxKb / 1024, 0).' MB') }} &middot; Maks. 500 baris &middot; Menimpa data semester yang sama.</p>

            <form method="POST" action="{{ route('mhs.impor.store') }}" enctype="multipart/form-data" class="mt-4">
                @csrf
                <label for="file" class="imp-dropzone" data-dropzone>
                    <input id="file" name="file" type="file" accept=".csv,.txt,.xlsx,.pdf" required class="imp-file-input">
                    <span class="imp-drop-icon" aria-hidden="true">
                        <x-icon name="upload-simple" />
                    </span>
                    <span class="text-sm font-semibold text-ink">Pilih file CSV, TXT, XLSX, atau PDF</span>
                    <span class="mt-1 text-xs text-ink-3">Klik di sini untuk memilih berkas KHS resmi UHAMKA atau lembar nilai OLU</span>
                    <span id="imp-file-name" class="imp-file-chip" style="display:none;"></span>
                </label>
                @error('file')<p class="field-error" role="alert">{{ $message }}</p>@enderror

                <div class="mt-5 flex flex-wrap items-center gap-2.5">
                    <button type="submit" class="btn btn-primary" style="border-radius:9999px !important;padding:0.55rem 1.25rem;font-weight:600;">
                        <x-icon name="file-arrow-up" /> Impor &amp; Hitung
                    </button>
                    <a href="{{ route('mhs.templat', ['format' => 'xlsx']) }}" class="btn btn-quiet btn-sm" style="border-radius:9999px !important;">
                        <x-icon name="download-simple" /> Templat XLSX
                    </a>
                    <a href="{{ route('mhs.templat', ['format' => 'csv']) }}" class="btn btn-quiet btn-sm" style="border-radius:9999px !important;">
                        <x-icon name="download-simple" /> Templat CSV
                    </a>
                </div>
            </form>
        </div>
    </section>

    <section class="lg:col-span-6">
        <div class="bg-surface" style="padding:1.5rem 1.6rem;">
            <h2 class="text-base font-semibold text-ink">Format kolom yang diterima</h2>
            <p class="mt-1 text-xs text-ink-3">Untuk PDF KHS resmi UHAMKA, kolom dibaca otomatis oleh sistem.</p>
            <div class="mt-4 overflow-x-auto" style="border:none !important;box-shadow:none !important;">
                <table class="tbl w-full text-xs">
                    <thead><tr><th>Kolom</th><th>Format</th><th class="text-right">Contoh</th></tr></thead>
                    <tbody>
                        <tr><td class="mono font-medium text-ink">semester</td><td>1–14</td><td class="mono text-right">6</td></tr>
                        <tr><td class="mono font-medium text-ink">kode_mk</td><td>Kode MK</td><td class="mono text-right">IF601</td></tr>
                        <tr><td class="mono font-medium text-ink">nama_mk</td><td>Nama MK</td><td class="mono text-right">Basis Data</td></tr>
                        <tr><td class="mono font-medium text-ink">sks</td><td>1–6</td><td class="mono text-right">3</td></tr>
                        <tr><td class="mono font-medium text-ink">nilai</td><td>A–E / 0–100 (opsional)</td><td class="mono text-right">B / 72</td></tr>
                        <tr><td class="mono font-medium text-ink">kehadiran_persen</td><td>0–100 (min. 75%)</td><td class="mono text-right">86</td></tr>
                        <tr><td class="mono font-medium text-ink">nilai_uts / tugas / uas</td><td>Opsional (0–100)</td><td class="mono text-right">65 / 75 / -</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

@if ($batches->isNotEmpty())
    <section class="mt-6 bg-surface" style="padding:1.5rem 1.6rem;">
        <h2 class="text-base font-semibold text-ink">Riwayat impor terakhir</h2>
        <div class="mt-3 overflow-x-auto" style="border:none !important;box-shadow:none !important;">
            <table class="tbl min-w-[32rem]">
                <thead><tr><th>Waktu</th><th>Berkas</th><th class="text-right">Baris</th><th class="text-right">Semester</th></tr></thead>
                <tbody>
                    @foreach ($batches as $b)
                        <tr>
                            <td class="num mono whitespace-nowrap text-xs text-ink-2">{{ $b->created_at->translatedFormat('j M Y, H:i') }}</td>
                            <td class="mono break-all text-xs font-medium text-ink">{{ $b->source_name }}</td>
                            <td class="num mono text-right">{{ $b->rows_valid }}</td>
                            <td class="num mono text-right">{{ implode(', ', $b->semesters) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    var inp = document.getElementById('file');
    var chip = document.getElementById('imp-file-name');
    if (inp && chip) {
        inp.addEventListener('change', function () {
            if (inp.files && inp.files.length > 0) {
                chip.textContent = inp.files[0].name;
                chip.style.display = 'inline-flex';
            } else {
                chip.style.display = 'none';
            }
        });
    }
});
</script>
@endsection