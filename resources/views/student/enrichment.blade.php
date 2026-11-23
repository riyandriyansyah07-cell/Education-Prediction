@extends('layouts.app')

@section('title', 'SKS Pengayaan (20 SKS)')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="rise text-3xl sm:text-4xl">SKS Pengayaan FTII</h1>
        <p class="rise mt-1 text-ink-2" style="--i: 1">Target wajib <strong>20 SKS</strong> (cicil Sem 1, konversi Sem 5+) &amp; <strong>wajib PKM</strong> maks. Sem 7.</p>
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

{{-- Kartu Progres 20 SKS, Wajib PKM, & Peringatan Dini --}}
<section class="risk-{{ $progress['risk_level'] }} mt-8 rounded-md border border-line bg-surface" style="padding:1.75rem;" aria-labelledby="progres-pengayaan">
    <div class="border-l-4 pl-5" style="border-color: var(--c);">
        <div class="flex flex-wrap items-center gap-2 text-xs text-ink-2">
            <span class="rounded border border-line-strong bg-bg px-2 py-0.5 font-medium">{{ $progress['phase_label'] }}</span>
            @php
                $pkmColor = $progress['pkm_status'] === 'selesai'
                    ? 'var(--ok)'
                    : ($progress['pkm_status'] === 'proses' ? 'var(--warn)' : ($progress['current_semester'] >= 7 ? 'var(--bad)' : 'var(--warn)'));
            @endphp
            <span class="rounded border border-line-strong bg-bg px-2 py-0.5 font-medium" style="color: {{ $pkmColor }};">
                Wajib PKM (maks. Sem 7): {{ $progress['pkm_label'] }}
            </span>
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
        <p class="mt-1 text-xs text-ink-3">{{ $progress['pkm_message'] }}</p>

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

<div class="mt-10 grid gap-8 lg:grid-cols-12" style="align-items:start;">
    {{-- Form Tambah / Perbarui MK Pengayaan --}}
    <section class="lg:col-span-5" aria-labelledby="form-pengayaan">
        <h2 id="form-pengayaan" class="text-xl">Catat / Perbarui Kegiatan</h2>
        <p class="mt-1 text-sm text-ink-2">SKS &amp; Dosen Pengampu otomatis. Tulis <strong>PKM</strong> untuk syarat wajib PKM.</p>

        <form method="POST" action="{{ route('mhs.pengayaan.store') }}" class="mt-4 space-y-4 rounded-md border border-line bg-surface p-5">
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
                <label for="activity_type" class="label">Jenis Kegiatan</label>
                <input id="activity_type" name="activity_type" type="text" class="input" required maxlength="160"
                    placeholder="Contoh: PKM-KC, Magang, Pengurus HIMA"
                    value="{{ old('activity_type') }}">
                @error('activity_type')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="status" class="label">Status Progres</label>
                <select id="status" name="status" class="input" required onchange="toggleConversionFields()">
                    <option value="dicicil" @selected(old('status', 'dicicil') === 'dicicil')>Sedang dicicil / berjalan</option>
                    <option value="siap_rekognisi" @selected(old('status') === 'siap_rekognisi')>Bukti siap rekognisi</option>
                    <option value="dikonversi" @selected(old('status') === 'dikonversi')>Sudah dikonversi di KRS (Sem 5+)</option>
                </select>
                @error('status')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2" style="align-items:end;">
                <div>
                    <label for="activity_semester" class="label">Semester Cicil</label>
                    <input id="activity_semester" name="activity_semester" type="number" min="1" max="14" class="input" required
                        value="{{ old('activity_semester', $progress['current_semester']) }}">
                    @error('activity_semester')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div id="conversion-sem-wrap">
                    <label for="conversion_semester" class="label">Sem. Konversi (Min. 5)</label>
                    <input id="conversion_semester" name="conversion_semester" type="number" min="5" max="14" class="input"
                        placeholder="5–8" value="{{ old('conversion_semester', max(5, $progress['current_semester'])) }}">
                    @error('conversion_semester')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div id="conversion-grade-wrap">
                <label for="grade" class="label">Nilai Akhir</label>
                <select id="grade" name="grade" class="input">
                    <option value="A" @selected(old('grade', 'A') === 'A')>Nilai A</option>
                    <option value="B" @selected(old('grade') === 'B')>Nilai B</option>
                    <option value="E" @selected(old('grade') === 'E')>Nilai E (tidak selesai sebelum UAS)</option>
                </select>
                @error('grade')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="evidence_notes" class="label">Catatan Bukti (Opsional)</label>
                <textarea id="evidence_notes" name="evidence_notes" rows="2" maxlength="500" class="input py-2"
                    placeholder="SK, Sertifikat, Proposal PKM, Laporan">{{ old('evidence_notes') }}</textarea>
                @error('evidence_notes')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn btn-primary w-full">Simpan Kegiatan</button>
        </form>
    </section>

    {{-- Daftar MK Pengayaan Mahasiswa --}}
    <section class="lg:col-span-7" aria-labelledby="daftar-pengayaan">
        <h2 id="daftar-pengayaan" class="text-xl">Daftar MK Pengayaan ({{ $records->count() }} MK)</h2>
        <p class="mt-1 text-sm text-ink-2">MK Pengayaan di KHS resmi otomatis tersinkron ke daftar ini.</p>

        @if ($records->isEmpty())
            <div class="mt-4 rounded-md border border-line-strong bg-surface text-center text-sm text-ink-2" style="padding:2rem; border-style:dashed;">
                Belum ada kegiatan pengayaan yang dicatat.
            </div>
        @else
            <div class="mt-4" style="display:flex; flex-direction:column; gap:1rem;">
                @foreach ($records as $r)
                    <article class="rounded-md border border-line bg-surface p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-medium">{{ $r->course_name }}</h3>
                                    <span class="mono rounded border border-line-strong bg-bg px-2 py-0.5 text-xs">{{ $r->credits }} SKS</span>
                                    @if (\App\Support\EnrichmentCatalog::isPkmRecord($r))
                                        <span class="rounded border border-accent/40 bg-accent-soft px-2 py-0.5 text-xs font-medium text-accent">PKM</span>
                                    @endif
                                    @if ($r->status === 'dikonversi')
                                        <span class="rounded border border-ok/40 bg-ok/10 px-2 py-0.5 text-xs font-medium" style="color:var(--ok);">Terkonversi &middot; Sem {{ $r->conversion_semester }} ({{ $r->grade }})</span>
                                    @elseif ($r->status === 'siap_rekognisi')
                                        <span class="rounded border border-accent/40 bg-accent-soft px-2 py-0.5 text-xs font-medium text-accent">Siap rekognisi</span>
                                    @else
                                        <span class="rounded border border-warn/40 bg-warn/10 px-2 py-0.5 text-xs font-medium" style="color:var(--warn);">Dicicil</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-sm text-ink-2">{{ $r->activity_type }} &middot; Sem {{ $r->activity_semester }}</p>
                                <p class="mt-1 text-xs text-ink-3">Dosen: {{ $r->lecturer }}</p>
                                @if ($r->evidence_notes)
                                    <p class="mt-1 text-xs text-ink-2">Bukti: {{ $r->evidence_notes }}</p>
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
                                    <input type="number" name="conversion_semester" min="5" max="14" value="{{ max(5, $progress['current_semester']) }}" class="input text-xs" style="width:4.5rem; padding:0.4rem 0.6rem;" aria-label="Semester konversi" title="Semester konversi (min. 5)">
                                    <select name="grade" class="input text-xs" style="width:5.25rem; padding:0.4rem 0.6rem;" aria-label="Nilai">
                                        <option value="A">Nilai A</option>
                                        <option value="B">Nilai B</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Konversi KRS</button>
                                </form>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif

        {{-- Referensi Katalog & Jalur Rekognisi Khusus (Bab 2) --}}
        <details class="mt-6 rounded-md border border-line bg-surface p-5">
            <summary class="cursor-pointer text-sm font-medium text-accent hover:underline">
                Katalog 22 MK Pengayaan &amp; Jalur Khusus (Hafiz / PKM / KRI)
            </summary>
            <div class="mt-4 space-y-5 text-sm">
                <div class="rounded-md border border-line bg-bg p-4">
                    <p class="font-medium">Jalur Rekognisi Khusus (Bab 2 Panduan 2025)</p>
                    <ul class="mt-2 list-disc space-y-1.5 pl-5 text-xs text-ink-2" style="line-height:1.6;">
                        <li><strong>Hafiz Al-Qur'an</strong>: 1–2 Juz (2 SKS) s.d. 20–30 Juz (langsung <strong>20 SKS</strong>).</li>
                        <li><strong>PKM (Wajib maks. Sem 7)</strong>: Universitas (2–3 SKS), Regional (8 SKS), <strong>PIMNAS</strong> (<strong>20 SKS</strong>).</li>
                        <li><strong>KRI</strong>: Lolos Seleksi (3 SKS), Regional (8 SKS), <strong>Nasional</strong> (<strong>20 SKS</strong>).</li>
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
                                            <span class="rounded border border-line bg-bg px-1.5 py-0.5 text-ink-3" style="margin-left:0.35rem; font-size:10px;">Kelas</span>
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
    const isConverted = document.getElementById('status').value === 'dikonversi';
    document.getElementById('conversion-sem-wrap').classList.toggle('hidden', !isConverted);
    document.getElementById('conversion-grade-wrap').classList.toggle('hidden', !isConverted);
}

document.addEventListener('DOMContentLoaded', () => {
    updateCourseMeta();
    toggleConversionFields();
});
</script>
@endsection