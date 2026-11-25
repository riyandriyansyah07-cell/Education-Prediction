@extends('layouts.app')

@section('title', 'SKS Pengayaan (20 SKS)')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="rise text-3xl sm:text-4xl">SKS Pengayaan FTII UHAMKA</h1>
        <p class="rise mt-2 max-w-[62ch] text-ink-2" style="--i: 1">
            Pantau target wajib <strong>20 SKS Mata Kuliah Pengayaan</strong> di luar kelas. Kegiatan bisa dicicil sejak semester 1 lewat rekognisi pengalaman lampau, lalu dikonversi ke KRS mulai semester 5 ke atas.
        </p>
    </div>
    <form method="GET" action="{{ route('mhs.pengayaan') }}" style="display:flex; align-items:center; gap:0.65rem;">
        <label for="sim-sem" class="text-xs text-ink-3" style="white-space:nowrap;">Posisi semester</label>
        <select id="sim-sem" name="semester" class="input text-sm" style="width:auto; min-width:8.5rem; padding-top:0.45rem; padding-bottom:0.45rem;" onchange="this.form.submit()">
            @for ($s = 1; $s <= 10; $s++)
                <option value="{{ $s }}" @selected($progress['current_semester'] === $s)>Semester {{ $s }}</option>
            @endfor
        </select>
    </form>
</div>

{{-- Kartu Progres 20 SKS & Peringatan Dini --}}
<section class="risk-{{ $progress['risk_level'] }} mt-8 rounded-md border border-line bg-surface" style="padding:1.75rem;" aria-labelledby="progres-pengayaan">
    <div class="border-l-4 pl-5" style="border-color: var(--c);">
        <div class="flex flex-wrap items-center gap-2 text-xs text-ink-2">
            <span class="rounded border border-line-strong bg-bg px-2 py-0.5 font-medium">{{ $progress['phase_label'] }}</span>
            <span class="rounded border border-line bg-bg px-2 py-0.5 text-ink-3">Panduan MK Pengayaan FTII 2025</span>
        </div>

        <div class="mt-3 flex flex-wrap items-baseline justify-between gap-4">
            <h2 id="progres-pengayaan" class="risk text-3xl sm:text-4xl">
                <span class="mono">{{ $progress['converted_sks'] }}</span>
                <span class="text-xl font-normal text-ink-3">/ {{ $progress['target_sks'] }} SKS terkonversi</span>
            </h2>
            <p class="mono text-sm text-ink-2">
                Terkonversi + bukti siap: <strong>{{ $progress['effective_sks'] }} SKS</strong>
            </p>
        </div>

        <p class="mt-3 max-w-[66ch] text-sm text-ink-2" style="line-height:1.6;">{{ $progress['status_message'] }}</p>

        {{-- Stacked Progress Bar --}}
        <div class="mt-5">
            <div class="flex h-3 w-full overflow-hidden rounded-md bg-bg" style="border:1px solid var(--line);" role="progressbar" aria-valuenow="{{ $progress['converted_sks'] }}" aria-valuemin="0" aria-valuemax="{{ $progress['target_sks'] }}">
                @if ($progress['converted_pct'] > 0)
                    <div style="width: {{ $progress['converted_pct'] }}%; background: var(--ok);" title="Sudah dikonversi KRS: {{ $progress['converted_sks'] }} SKS"></div>
                @endif
                @if ($progress['ready_pct'] > 0)
                    <div style="width: {{ $progress['ready_pct'] }}%; background: var(--accent);" title="Bukti siap rekognisi: {{ $progress['ready_sks'] }} SKS"></div>
                @endif
                @if ($progress['in_progress_pct'] > 0)
                    <div style="width: {{ $progress['in_progress_pct'] }}%; background: var(--warn);" title="Sedang dicicil: {{ $progress['in_progress_sks'] }} SKS"></div>
                @endif
            </div>
            <div class="mt-3 text-xs text-ink-3" style="display:flex; flex-wrap:wrap; gap:0.5rem 1.5rem;">
                <span style="display:inline-flex; align-items:center; gap:0.45rem;">
                    <span style="width:9px; height:9px; border-radius:9999px; display:inline-block; background:var(--ok);"></span>
                    Terkonversi KRS ({{ $progress['converted_sks'] }} SKS)
                </span>
                <span style="display:inline-flex; align-items:center; gap:0.45rem;">
                    <span style="width:9px; height:9px; border-radius:9999px; display:inline-block; background:var(--accent);"></span>
                    Bukti siap rekognisi ({{ $progress['ready_sks'] }} SKS)
                </span>
                <span style="display:inline-flex; align-items:center; gap:0.45rem;">
                    <span style="width:9px; height:9px; border-radius:9999px; display:inline-block; background:var(--warn);"></span>
                    Sedang dicicil ({{ $progress['in_progress_sks'] }} SKS)
                </span>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-md border border-line bg-bg" style="padding:1rem 1.15rem;">
            <p class="text-xs text-ink-3">Sudah Konversi KRS</p>
            <p class="mono mt-2 text-2xl font-medium" style="color:var(--ok);">{{ $progress['converted_sks'] }} <span class="text-xs font-normal text-ink-3">SKS</span></p>
        </div>
        <div class="rounded-md border border-line bg-bg" style="padding:1rem 1.15rem;">
            <p class="text-xs text-ink-3">Bukti Siap Rekognisi</p>
            <p class="mono mt-2 text-2xl font-medium text-accent">{{ $progress['ready_sks'] }} <span class="text-xs font-normal text-ink-3">SKS</span></p>
        </div>
        <div class="rounded-md border border-line bg-bg" style="padding:1rem 1.15rem;">
            <p class="text-xs text-ink-3">Sedang Dicicil</p>
            <p class="mono mt-2 text-2xl font-medium" style="color:var(--warn);">{{ $progress['in_progress_sks'] }} <span class="text-xs font-normal text-ink-3">SKS</span></p>
        </div>
        <div class="rounded-md border border-line bg-bg" style="padding:1rem 1.15rem;">
            <p class="text-xs text-ink-3">Sisa Wajib Dikonversi</p>
            <p class="mono mt-2 text-2xl font-medium text-ink">{{ $progress['remaining_converted'] }} <span class="text-xs font-normal text-ink-3">SKS</span></p>
        </div>
    </div>
</section>


{{-- Kartu 4 Syarat Wajib SKPI & Tahap PKM FTII --}}
@php
    $skpi = $progress['skpi'] ?? ['completed' => 0, 'total' => 4, 'pct' => 0, 'items' => []];
    $stObj = $setting ?? null;
@endphp
<style>
    .skpi-card {
        margin-top: 2rem;
        border-radius: 12px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: 1.5rem 1.65rem;
    }
    .skpi-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-top: 1.25rem;
    }
    .skpi-item {
        border-radius: 10px;
        border: 1px solid var(--line);
        background: var(--bg);
        padding: 1rem 1.1rem;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }
    .skpi-item.is-done {
        border-color: color-mix(in srgb, var(--ok) 45%, var(--line));
    }
    .skpi-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.16rem 0.55rem;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 600;
        border: 1px solid var(--line);
        background: var(--surface);
        color: var(--ink-2);
        white-space: nowrap;
    }
    .skpi-badge.is-ok {
        color: var(--ok);
        border-color: color-mix(in srgb, var(--ok) 40%, var(--line));
        background: color-mix(in srgb, var(--ok) 12%, var(--surface));
    }
    .skpi-badge.is-warn {
        color: var(--warn);
        border-color: color-mix(in srgb, var(--warn) 40%, var(--line));
        background: color-mix(in srgb, var(--warn) 12%, var(--surface));
    }
    @media (max-width: 768px) {
        .skpi-grid { grid-template-columns: 1fr; }
        .skpi-card { padding: 1.15rem; }
    }
</style>

<section class="skpi-card" aria-labelledby="skpi-ftii">
    <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:0.75rem;">
        <div>
            <h2 id="skpi-ftii" class="text-xl font-semibold">4 Syarat Wajib SKPI &amp; Tahap PKM FTII</h2>
            <p class="mt-1 text-sm text-ink-2">
                Perbarui progres 4 sertifikat wajib SKPI kelulusan FTII UHAMKA. Status ini juga tampil otomatis di akun DPA kamu.
            </p>
        </div>
        <span class="skpi-badge {{ $skpi['completed'] === 4 ? 'is-ok' : 'is-warn' }} mono">
            {{ $skpi['completed'] }}/{{ $skpi['total'] }} SKPI Tuntas ({{ $skpi['pct'] }}%)
        </span>
    </div>

    <div class="mt-3" style="height:6px;width:100%;border-radius:9999px;background:var(--bg);border:1px solid var(--line);overflow:hidden;">
        <div style="height:100%;width:{{ $skpi['pct'] }}%;background:var(--ok);border-radius:9999px;transition:width 0.3s ease;"></div>
    </div>

    <form method="POST" action="{{ route('mhs.pengayaan.skpi') }}">
        @csrf
        <div class="skpi-grid">
            {{-- 1. Sertifikat Kompetensi IT --}}
            @php $itVal = old('skpi_it_status', $stObj?->skpi_it_status ?? 'belum'); @endphp
            <div class="skpi-item {{ $itVal === 'selesai' ? 'is-done' : '' }}">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
                    <span class="text-sm font-semibold">1. Sertifikat Kompetensi IT</span>
                    <span class="skpi-badge {{ $itVal === 'selesai' ? 'is-ok' : ($itVal === 'proses' ? 'is-warn' : '') }}">
                        {{ $itVal === 'selesai' ? 'Tuntas' : ($itVal === 'proses' ? 'Proses' : 'Belum') }}
                    </span>
                </div>
                <p class="text-xs text-ink-3">Wajib dari lembaga kredibel seperti <strong>Mahira</strong> atau <strong>BNSP</strong>.</p>
                <div style="display:grid;gap:0.55rem;grid-template-columns:1fr 1fr;">
                    <div>
                        <label for="skpi_it_status" class="label text-xs">Status</label>
                        <select id="skpi_it_status" name="skpi_it_status" class="input text-sm">
                            <option value="belum" @selected($itVal === 'belum')>Belum ikut</option>
                            <option value="proses" @selected($itVal === 'proses')>Sedang proses / uji</option>
                            <option value="selesai" @selected($itVal === 'selesai')>Lulus &amp; bersertifikat</option>
                        </select>
                    </div>
                    <div>
                        <label for="skpi_it_provider" class="label text-xs">Lembaga Sertifikasi</label>
                        <input id="skpi_it_provider" name="skpi_it_provider" type="text" list="skpi-it-providers" class="input text-sm" maxlength="80"
                            placeholder="Mahira / BNSP" value="{{ old('skpi_it_provider', $stObj?->skpi_it_provider) }}">
                        <datalist id="skpi-it-providers">
                            <option value="Mahira">
                            <option value="BNSP">
                        </datalist>
                    </div>
                </div>
            </div>

            {{-- 2. Baitul Arqam Purna Studi (BAPS) --}}
            @php $bapsVal = old('skpi_baps_status', $stObj?->skpi_baps_status ?? 'belum'); @endphp
            <div class="skpi-item {{ $bapsVal === 'selesai' ? 'is-done' : '' }}">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
                    <span class="text-sm font-semibold">2. Baitul Arqam Purna Studi (BAPS)</span>
                    <span class="skpi-badge {{ $bapsVal === 'selesai' ? 'is-ok' : ($bapsVal === 'terdaftar' ? 'is-warn' : '') }}">
                        {{ $bapsVal === 'selesai' ? 'Tuntas' : ($bapsVal === 'terdaftar' ? 'Terdaftar' : 'Belum') }}
                    </span>
                </div>
                <p class="text-xs text-ink-3">Wajib mengikuti kegiatan BAPS hingga memperoleh sertifikat.</p>
                <div>
                    <label for="skpi_baps_status" class="label text-xs">Status Keikutsertaan</label>
                    <select id="skpi_baps_status" name="skpi_baps_status" class="input text-sm">
                        <option value="belum" @selected($bapsVal === 'belum')>Belum mengikuti</option>
                        <option value="terdaftar" @selected($bapsVal === 'terdaftar')>Sudah terdaftar / sedang berjalan</option>
                        <option value="selesai" @selected($bapsVal === 'selesai')>Selesai &amp; bersertifikat BAPS</option>
                    </select>
                </div>
            </div>

            {{-- 3. Sertifikat PKM --}}
            @php
                $pkmVal = old('skpi_pkm_stage', $skpi['pkm_stage'] ?? ($stObj?->skpi_pkm_stage ?? 'belum'));
                $pkmOk = in_array($pkmVal, ['universitas', 'pendanaan'], true);
            @endphp
            <div class="skpi-item {{ $pkmOk ? 'is-done' : '' }}">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
                    <span class="text-sm font-semibold">3. Sertifikat PKM (Wajib FTII)</span>
                    <span class="skpi-badge {{ $pkmOk ? 'is-ok' : ($pkmVal === 'fakultas' ? 'is-warn' : '') }}">
                        {{ $progress['pkm_label'] }}
                    </span>
                </div>
                <p class="text-xs text-ink-3">
                    Diikuti tiap <strong>semester ganjil</strong> (mulai Sem 1). Wajib lolos minimal <strong>tahap Universitas</strong> agar dapat Sertifikat PKM (SKPI); lolos Fakultas saja belum diakui SKPI.
                </p>
                <div>
                    <label for="skpi_pkm_stage" class="label text-xs">Tahap Pencapaian PKM</label>
                    <select id="skpi_pkm_stage" name="skpi_pkm_stage" class="input text-sm">
                        <option value="belum" @selected($pkmVal === 'belum')>Belum mengikuti PKM</option>
                        <option value="fakultas" @selected($pkmVal === 'fakultas')>Lolos Tahap Fakultas (belum diakui SKPI)</option>
                        <option value="universitas" @selected($pkmVal === 'universitas')>Lolos Tahap Universitas (Sertifikat PKM &amp; diakui SKPI)</option>
                        <option value="pendanaan" @selected($pkmVal === 'pendanaan')>Lolos Pendanaan / PIMNAS (diakui SKPI + rekognisi SKS)</option>
                    </select>
                </div>
            </div>

            {{-- 4. Sertifikat TOEFL UHAMKA --}}
            @php $toeflVal = old('skpi_toefl_status', $stObj?->skpi_toefl_status ?? 'belum'); @endphp
            <div class="skpi-item {{ $toeflVal === 'selesai' ? 'is-done' : '' }}">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
                    <span class="text-sm font-semibold">4. Sertifikat TOEFL UHAMKA</span>
                    <span class="skpi-badge {{ $toeflVal === 'selesai' ? 'is-ok' : ($toeflVal === 'proses' ? 'is-warn' : '') }}">
                        {{ $toeflVal === 'selesai' ? 'Tuntas' : ($toeflVal === 'proses' ? 'Proses' : 'Belum') }}
                    </span>
                </div>
                <p class="text-xs text-ink-3">Sertifikat TOEFL <strong>harus diterbitkan oleh UHAMKA</strong>.</p>
                <div style="display:grid;gap:0.55rem;grid-template-columns:1.3fr 0.7fr;">
                    <div>
                        <label for="skpi_toefl_status" class="label text-xs">Status TOEFL UHAMKA</label>
                        <select id="skpi_toefl_status" name="skpi_toefl_status" class="input text-sm">
                            <option value="belum" @selected($toeflVal === 'belum')>Belum tes</option>
                            <option value="proses" @selected($toeflVal === 'proses')>Terdaftar / menunggu hasil</option>
                            <option value="selesai" @selected($toeflVal === 'selesai')>Lulus &amp; bersertifikat UHAMKA</option>
                        </select>
                    </div>
                    <div>
                        <label for="skpi_toefl_score" class="label text-xs">Skor (Opsional)</label>
                        <input id="skpi_toefl_score" name="skpi_toefl_score" type="number" min="200" max="677" class="input text-sm"
                            placeholder="450" value="{{ old('skpi_toefl_score', $stObj?->skpi_toefl_score) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-ink-3">{{ $progress['pkm_message'] }}</p>
            <button type="submit" class="btn btn-primary btn-sm">Simpan Status SKPI &amp; PKM</button>
        </div>
    </form>
</section>

<div class="mt-12 grid gap-10 lg:grid-cols-12">
    {{-- Form Tambah / Perbarui MK Pengayaan --}}
    <section class="lg:col-span-5" aria-labelledby="form-pengayaan">
        <h2 id="form-pengayaan" class="text-xl">Catat / Perbarui Kegiatan</h2>
        <p class="mt-1 text-sm text-ink-2">Pilih dari 22 MK Pengayaan resmi FTII UHAMKA. SKS dan Dosen Pengampu terisi otomatis.</p>

        <form method="POST" action="{{ route('mhs.pengayaan.store') }}" class="mt-5 space-y-4 rounded-md border border-line bg-surface p-5">
            @csrf

            <div>
                <label for="course_code" class="label">Mata Kuliah Pengayaan</label>
                <select id="course_code" name="course_code" class="input" required onchange="updateCourseMeta()">
                    <option value="">-- Pilih MK Pengayaan (22 pilihan) --</option>
                    @foreach ($catalog as $item)
                        <option
                            value="{{ $item['code'] }}"
                            data-credits="{{ $item['credits'] }}"
                            data-lecturer="{{ $item['lecturer'] }}"
                            data-competency="{{ $item['competency'] }}"
                            data-activities="{{ $item['activities'] }}"
                            @selected(old('course_code') === $item['code'])
                        >
                            {{ $item['name'] }} ({{ $item['credits'] }} SKS){{ in_array($item['code'], $takenCodes, true) ? ' — sudah dicatat' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('course_code')<p class="field-error">{{ $message }}</p>@enderror
                <div id="course-meta" class="mt-2 hidden rounded-md border border-line bg-bg text-xs text-ink-2" style="padding:0.75rem 0.9rem; line-height:1.55;">
                    <p><strong>Dosen Pengampu:</strong> <span id="meta-lecturer"></span></p>
                    <p class="mt-1"><strong>Contoh kegiatan:</strong> <span id="meta-activities"></span></p>
                </div>
            </div>

            <div>
                <label for="activity_type" class="label">Jenis Kegiatan yang Diikuti</label>
                <input id="activity_type" name="activity_type" type="text" class="input" required maxlength="160"
                    placeholder="Contoh: Pengurus HIMA, Magang 1 semester, PKM-KC"
                    value="{{ old('activity_type') }}">
                @error('activity_type')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2" style="align-items:end;">
                <div>
                    <label for="activity_semester" class="label">Semester Cicil</label>
                    <input id="activity_semester" name="activity_semester" type="number" min="1" max="14" class="input" required
                        value="{{ old('activity_semester', $progress['current_semester']) }}">
                    @error('activity_semester')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="status" class="label">Status Progres</label>
                    <select id="status" name="status" class="input" required onchange="toggleConversionFields()">
                        <option value="dicicil" @selected(old('status', 'dicicil') === 'dicicil')>Sedang dicicil</option>
                        <option value="siap_rekognisi" @selected(old('status') === 'siap_rekognisi')>Bukti siap rekognisi</option>
                        <option value="dikonversi" @selected(old('status') === 'dikonversi')>Sudah konversi KRS</option>
                    </select>
                    @error('status')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div id="conversion-fields" class="grid gap-4 sm:grid-cols-2" style="align-items:end;">
                <div>
                    <label for="conversion_semester" class="label">Sem. Konversi (Min. 5)</label>
                    <input id="conversion_semester" name="conversion_semester" type="number" min="5" max="14" class="input"
                        placeholder="5–8" value="{{ old('conversion_semester', max(5, $progress['current_semester'])) }}">
                    @error('conversion_semester')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="grade" class="label">Nilai Akhir</label>
                    <select id="grade" name="grade" class="input">
                        <option value="">-- Pilih nilai --</option>
                        <option value="A" @selected(old('grade') === 'A')>A</option>
                        <option value="B" @selected(old('grade') === 'B')>B</option>
                        <option value="C" @selected(old('grade') === 'C')>C</option>
                        <option value="D" @selected(old('grade') === 'D')>D</option>
                        <option value="E" @selected(old('grade') === 'E')>E (tidak selesai)</option>
                    </select>
                    @error('grade')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="evidence_notes" class="label">Catatan Bukti Kinerja (Opsional)</label>
                <textarea id="evidence_notes" name="evidence_notes" rows="2" maxlength="500" class="input py-2"
                    placeholder="Contoh: SK Pengurus, Sertifikat, Laporan Kegiatan">{{ old('evidence_notes') }}</textarea>
                @error('evidence_notes')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn btn-primary w-full">Simpan ke Daftar Pengayaan</button>
        </form>
    </section>

    {{-- Daftar MK Pengayaan Mahasiswa --}}
    <section class="lg:col-span-7" aria-labelledby="daftar-pengayaan">
        <h2 id="daftar-pengayaan" class="text-xl">Daftar MK Pengayaan Kamu ({{ $records->count() }} MK)</h2>
        <p class="mt-1 text-sm text-ink-2">Konsultasikan berkas persyaratan dan bukti kinerja ke Dosen Pengampu sebelum pengisian KRS.</p>

        @if ($records->isEmpty())
            <div class="mt-5 rounded-md border border-line-strong bg-surface text-center text-sm text-ink-2" style="padding:2rem; border-style:dashed;">
                Belum ada kegiatan pengayaan yang dicatat. Mulai cicil dari formulir di samping agar target 20 SKS tidak menumpuk di semester akhir.
            </div>
        @else
            <div class="mt-5 space-y-4">
                @foreach ($records as $r)
                    <article class="rounded-md border border-line bg-surface p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-medium">{{ $r->course_name }}</h3>
                                    <span class="mono rounded border border-line-strong bg-bg px-2 py-0.5 text-xs">{{ $r->credits }} SKS</span>
                                    @if ($r->status === 'dikonversi')
                                        <span class="rounded border border-ok/40 bg-ok/10 px-2 py-0.5 text-xs font-medium" style="color:var(--ok);">Terkonversi KRS &middot; Sem {{ $r->conversion_semester }} ({{ $r->grade }})</span>
                                    @elseif ($r->status === 'siap_rekognisi')
                                        <span class="rounded border border-accent/40 bg-accent-soft px-2 py-0.5 text-xs font-medium text-accent">Bukti siap rekognisi</span>
                                    @else
                                        <span class="rounded border border-warn/40 bg-warn/10 px-2 py-0.5 text-xs font-medium" style="color:var(--warn);">Sedang dicicil{{ $r->grade === 'E' ? ' · Nilai E (gagal)' : '' }}</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-sm text-ink-2">{{ $r->activity_type }} &middot; Dicicil Sem {{ $r->activity_semester }}</p>
                                <p class="mt-1 text-xs text-ink-3">Dosen Pengampu: {{ $r->lecturer }}</p>
                                @if ($r->evidence_notes)
                                    <p class="mt-1.5 text-xs text-ink-2">Bukti: {{ $r->evidence_notes }}</p>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('mhs.pengayaan.destroy', $r) }}" onsubmit="return confirm('Hapus catatan MK Pengayaan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-quiet btn-sm text-ink-3">Hapus</button>
                            </form>
                        </div>

                        @if ($r->status !== 'dikonversi')
                            <div class="mt-4 border-t border-line pt-4" style="display:flex; flex-direction:column; gap:0.75rem;">
                                @if ($r->status === 'dicicil')
                                    <form method="POST" action="{{ route('mhs.pengayaan.status', $r) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="siap_rekognisi">
                                        <button type="submit" class="btn btn-quiet btn-sm">Tandai Bukti Siap Rekognisi</button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('mhs.pengayaan.status', $r) }}" style="display:flex; flex-wrap:wrap; align-items:center; gap:0.5rem;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="dikonversi">
                                    <span class="text-xs text-ink-3">Konversi KRS:</span>
                                    <input type="number" name="conversion_semester" min="5" max="14" value="{{ max(5, $progress['current_semester']) }}" class="input text-xs" style="width:4.5rem; padding:0.4rem 0.6rem;" aria-label="Semester konversi" title="Semester konversi (min. 5)" required>
                                    <select name="grade" class="input text-xs" style="width:6.25rem; padding:0.4rem 0.6rem;" aria-label="Nilai" required>
                                        <option value="">Nilai...</option>
                                        <option value="A">Nilai A</option>
                                        <option value="B">Nilai B</option>
                                        <option value="C">Nilai C</option>
                                        <option value="D">Nilai D</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Tandai Sudah Konversi KRS</button>
                                </form>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif

        {{-- Referensi Katalog & Jalur Rekognisi Khusus (Bab 2) --}}
        <details class="mt-8 rounded-md border border-line bg-surface p-5">
            <summary class="cursor-pointer text-sm font-medium text-accent hover:underline">
                Lihat Katalog Lengkap 22 MK Pengayaan &amp; Jalur Rekognisi Khusus (Hafiz / PKM / KRI)
            </summary>
            <div class="mt-4 space-y-5 text-sm">
                <div class="rounded-md border border-line bg-bg p-4">
                    <p class="font-medium">Jalur Rekognisi Kompetensi Khusus (Bab 2 Panduan 2025)</p>
                    <ul class="mt-2 list-disc space-y-1.5 pl-5 text-xs text-ink-2" style="line-height:1.6;">
                        <li><strong>Hafalan Al-Qur'an (Hafiz)</strong>: 1–2 Juz (2 SKS), 4–6 Juz (4 SKS), 7–8 Juz (6 SKS), 9–10 Juz (8 SKS), 11–12 Juz (10 SKS), 13–14 Juz (12 SKS), 15–16 Juz (14 SKS), 16–17 Juz (16 SKS), 18–19 Juz (18 SKS), 20–30 Juz (langsung <strong>20 SKS</strong>).</li>
                        <li><strong>Prestasi PKM</strong>: Lolos Universitas (2–3 SKS), Lolos Pendanaan Regional (8 SKS), Lolos <strong>PIMNAS</strong> (langsung <strong>20 SKS</strong>).</li>
                        <li><strong>Prestasi KRI (Kontes Robot Indonesia)</strong>: Lolos Seleksi (3 SKS), Lolos Regional (8 SKS), Lolos <strong>Nasional</strong> (langsung <strong>20 SKS</strong>).</li>
                    </ul>
                </div>

                <div class="overflow-x-auto">
                    <table class="tbl w-full text-left text-xs">
                        <thead>
                            <tr>
                                <th>Mata Kuliah</th>
                                <th>SKS</th>
                                <th>Dosen Pengampu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($catalog as $item)
                                <tr>
                                    <td class="font-medium">
                                        {{ $item['name'] }}
                                        @if ($item['is_class_based'])
                                            <span class="rounded border border-line bg-bg px-1.5 py-0.5 text-ink-3" style="margin-left:0.35rem; font-size:10px;">Kelas/Coaching</span>
                                        @endif
                                    </td>
                                    <td class="mono">{{ $item['credits'] }}</td>
                                    <td class="text-ink-2">{{ $item['lecturer'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
    </section>
</div>

<script>
function updateCourseMeta() {
    const sel = document.getElementById('course_code');
    const opt = sel.options[sel.selectedIndex];
    const box = document.getElementById('course-meta');
    if (!opt || !opt.value) {
        box.classList.add('hidden');
        return;
    }
    document.getElementById('meta-lecturer').textContent = opt.dataset.lecturer || '-';
    document.getElementById('meta-activities').textContent = opt.dataset.activities || '-';
    box.classList.remove('hidden');
}

function toggleConversionFields() {
    const status = document.getElementById('status').value;
    const isConverted = status === 'dikonversi';
    const fields = document.getElementById('conversion-fields');
    const semInput = document.getElementById('conversion_semester');
    const gradeSelect = document.getElementById('grade');
    fields.classList.toggle('hidden', !isConverted);
    if (semInput) semInput.required = isConverted;
    if (gradeSelect) gradeSelect.required = isConverted;
}

document.addEventListener('DOMContentLoaded', () => {
    updateCourseMeta();
    toggleConversionFields();
});
</script>
@endsection