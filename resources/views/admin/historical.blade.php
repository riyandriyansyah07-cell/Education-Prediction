@extends('layouts.app')

@section('title', 'Data historis')

@section('content')
<h1 class="rise text-3xl sm:text-4xl">Data historis anonim</h1>
<p class="rise mt-1 text-ink-2" style="--i: 1">Dataset latih/uji model tanpa kolom identitas (nama, NIM, email).</p>

<div class="mt-8">@include('partials.import-errors')</div>

<div class="mt-8 grid gap-10 lg:grid-cols-12">
    <section class="lg:col-span-5">
        <div class="rounded-md border border-line bg-surface p-6">
            <h2 class="text-lg font-medium">Unggah dataset</h2>
            <form method="POST" action="{{ route('admin.historis.store') }}" enctype="multipart/form-data" class="mt-4">
                @csrf
                <div data-dropzone class="rounded-md border border-dashed border-line-strong bg-bg p-6 transition-colors data-[over]:border-accent data-[over]:bg-accent-soft">
                    <label for="file" class="label">Pilih CSV atau XLSX</label>
                    <input id="file" name="file" type="file" accept=".csv,.txt,.xlsx" required
                           class="block w-full text-sm file:mr-4 file:h-9 file:cursor-pointer file:rounded-md file:border file:border-line-strong file:bg-surface file:px-3 file:text-ink hover:file:bg-sunken">
                    @error('file')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <p class="mt-3 text-xs text-ink-3">10 variabel inti + <span class="mono">label</span> (rendah/sedang/tinggi) · 100–5.000 baris</p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <button class="btn btn-primary btn-sm" type="submit"><x-icon name="file-arrow-up" /> Unggah</button>
                    <a href="{{ route('admin.historis.templat', ['format' => 'xlsx']) }}" class="btn btn-quiet btn-sm"><x-icon name="download-simple" /> XLSX</a>
                    <a href="{{ route('admin.historis.templat', ['format' => 'csv']) }}" class="btn btn-quiet btn-sm"><x-icon name="download-simple" /> CSV</a>
                </div>
            </form>
        </div>
    </section>

    <section class="lg:col-span-7">
        <h2 class="text-lg font-medium">Dataset tersimpan</h2>
        @if ($datasets->isEmpty())
            <p class="mt-4 text-sm text-ink-2">Belum ada dataset.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="tbl min-w-[34rem]">
                    <thead><tr><th>Berkas</th><th>Baris</th><th>R</th><th>S</th><th>T</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                    <tbody>
                        @foreach ($datasets as $d)
                            <tr>
                                <td>
                                    <span class="mono break-all text-sm">{{ $d->name }}</span>
                                    @if ($d->is_synthetic) <span class="ml-1 rounded border border-warn/40 bg-warn/10 px-1.5 py-0.5 text-xs">Demo</span> @endif
                                    <span class="block text-xs text-ink-3">{{ $d->created_at->translatedFormat('j M Y') }}</span>
                                </td>
                                <td class="num">{{ \App\Support\Fmt::num($d->row_count, 0) }}</td>
                                <td class="num">{{ $d->label_counts['rendah'] ?? 0 }}</td>
                                <td class="num">{{ $d->label_counts['sedang'] ?? 0 }}</td>
                                <td class="num">{{ $d->label_counts['tinggi'] ?? 0 }}</td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('admin.historis.hapus', $d) }}" data-confirm="Hapus dataset ini?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection