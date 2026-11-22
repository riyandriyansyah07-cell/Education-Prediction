@extends('layouts.app')

@section('title', 'SKS Pengayaan (20 SKS)')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="rise text-3xl sm:text-4xl">SKS Pengayaan FTII UHAMKA</h1>
        <p class="rise mt-2 max-w-[65ch] text-ink-2" style="--i: 1">
            Catat dan pantau target wajib <strong>20 SKS Mata Kuliah Pengayaan</strong> di luar kelas. Kegiatan bisa dicicil sejak semester 1 lewat rekognisi pengalaman lampau, lalu dikonversi ke KRS mulai semester 5 ke atas.
        </p>
    </div>
    <form method="GET" action="{{ route('mhs.pengayaan') }}" class="flex items-center gap-2">
        <label for="sim-sem" class="text-xs text-ink-3">Posisi semester:</label>
        <select id="sim-sem" name="semester" class="input w-auto py-1.5 text-sm" onchange="this.form.submit()">
            @for ($s = 1; $s <= 10; $s++)
                <option value="{{ $s }}" @selected($progress['current_semester'] === $s)>Semester {{ $s }}</option>
            @endfor
        </select>
    </form>
</div>

{{-- Kartu Progres 20 SKS & Peringatan Dini --}}
<section class="risk-{{ $progress['risk_level'] }} mt-8 rounded-xl border border-line bg-surface p-6 sm:p-8" aria-labelledby="progres-pengayaan">
    <div class="border-l-4 pl-5" style="border-color: var(--c)">
        <div class="flex flex-wrap items-center gap-2 text-xs text-ink-2">
            <span class="rounded border border-line-strong bg-bg px-2 py-0.5 font-medium">{{ $progress['phase_label'] }}</span>
            <span class="rounded border border-line bg-bg px-2 py-0.5 text-ink-3">Panduan MK Pengayaan FTII 2025</span>
        </div>

        <div class="mt-3 flex flex-wrap items-baseline justify-between gap-4">
            <h2 id="progres-pengayaan" class="risk text-3xl sm:text-4xl">
                {{ $progress['converted_sks'] }} <span class="text-xl font-normal text-ink-3">/ {{ $progress['target_sks'] }} SKS terkonversi</span>
            </h2>
            <p class="mono text-sm text-ink-2">
                Bukti siap + terkonversi: <strong>{{ $progress['effective_sks'] }} SKS</strong>
            </p>
        </div>

        <p class="mt-3 max-w-[68ch] text-sm text-ink-2">{{ $progress['status_message'] }}</p>

        {{-- Stacked Progress Bar --}}
        <div class="mt-5">
            <div class="flex h-3 w-full overflow-hidden rounded-md bg-bg" role="progressbar" aria-valuenow="{{ $progress['converted_sks'] }}" aria-valuemin="0" aria-valuemax="{{ $progress['target_sks'] }}">
                @if ($progress['converted_pct'] > 0)
                    <div style="width: {{ $progress['converted_pct'] }}%; background: var(--ok)" title="Sudah dikonversi KRS: {{ $progress['converted_sks'] }} SKS"></div>
                @endif
                @if ($progress['ready_pct'] > 0)
                    <div style="width: {{ $progress['ready_pct'] }}%; background: var(--accent)" title="Bukti siap rekognisi: {{ $progress['ready_sks'] }} SKS"></div>
                @endif
                @if ($progress['in_progress_pct'] > 0)
                    <div style="width: {{ $progress['in_progress_pct'] }}%; background: var(--warn)" title="Sedang dicicil: {{ $progress['in_progress_sks'] }} SKS"></div>
                @endif
            </div>
            <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-ink-3">
                <span class="inline-flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-full" style="background: var(--ok)"></span> Terkonversi KRS ({{ $progress['converted_sks'] }} SKS)</span>
                <span class="inline-flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-full" style="background: var(--accent)"></span> Bukti siap rekognisi ({{ $progress['ready_sks'] }} SKS)</span>
                <span class="inline-flex items-center gap-1.5"><span class="inline-block size-2.5 rounded-full" style="background: var(--warn)"></span> Sedang dicicil ({{ $progress['in_progress_sks'] }} SKS)</span>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-4">
        <div class="rounded-lg border border-line bg-bg p-3.5">
            <p class="text-xs text-ink-3">Sudah Konversi KRS</p>
            <p class="mono mt-1 text-2xl font-medium">{{ $progress['converted_sks'] }} <span class="text-xs font-normal text-ink-3">SKS</span></p>
        </div>
        <div class="rounded-lg border border-line bg-bg p-3.5">
            <p class="text-xs text-ink-3">Bukti Siap Rekognisi</p>
            <p class="mono mt-1 text-2xl font-medium">{{ $progress['ready_sks'] }} <span class="text-xs font-normal text-ink-3">SKS</span></p>
        </div>
        <div class="rounded-lg border border-line bg-bg p-3.5">
            <p class="text-xs text-ink-3">Sedang Dicicil</p>
            <p class="mono mt-1 text-2xl font-medium">{{ $progress['in_progress_sks'] }} <span class="text-xs font-normal text-ink-3">SKS</span></p>
        </div>
        <div class="rounded-lg border border-line bg-bg p-3.5">
            <p class="text-xs text-ink-3">Sisa Wajib Dikonversi</p>
            <p class="mono mt-1 text-2xl font-medium">{{ $progress['remaining_converted'] }} <span class="text-xs font-normal text-ink-3">SKS</span></p>
        </div>
    </div>
</section>

<div class="mt-12 grid gap-10 lg:grid-cols-12">
    {{-- Form Tambah / Perbarui MK Pengayaan --}}
    <section class="lg:col-span-5" aria-labelledby="form-pengayaan">
        <h2 id="form-pengayaan" class="text-xl">Catat / Perbarui Kegiatan Pengayaan</h2>
        <p class="mt-1 text-sm text-ink-2">Pilih dari 22 MK Pengayaan resmi FTII UHAMKA. Sistem otomatis menampilkan SKS dan Dosen Pengampunya.</p>

        <form method="POST" action="{{ route('mhs.pengayaan.store') }}" class="mt-5 space-y-4 rounded-xl border border-line bg-surface p-5">
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
                <div id="course-meta" class="mt-2 hidden rounded-md border border-line bg-bg p-3 text-xs text-ink-2">
                    <p><strong>Dosen Pengampu:</strong> <span id="meta-lecturer"></span></p>
                    <p class="mt-1"><strong>Contoh kegiatan:</strong> <span id="meta-activities"></span></p>
                </div>
            </div>

            <div>
                <label for="activity_type" class="label">Jenis Kegiatan yang Diikuti</label>
                <input id="activity_type" name="activity_type" type="text" class="input" required maxlength="160"
                    placeholder="Contoh: Pengurus HIMA, Magang 1 semester, PKM-KC, Sertifikasi LSP"
                    value="{{ old('activity_type') }}">
                @error('activity_type')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="activity_semester" class="label">Semester Pelaksanaan / Cicil</label>
                    <input id="activity_semester" name="activity_semester" type="number" min="1" max="14" class="input" required
                        value="{{ old('activity_semester', $progress['current_semester']) }}">
                    @error('activity_semester')<p class="field-error">{{ $message }}</p>@enderror
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
            </div>

            <div id="conversion-fields" class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="conversion_semester" class="label">Semester Konversi KRS (Min. 5)</label>
                    <input id="conversion_semester" name="conversion_semester" type="number" min="5" max="14" class="input"
                        placeholder="5–8" value="{{ old('conversion_semester', max(5, $progress['current_semester'])) }}">
                    @error('conversion_semester')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="grade" class="label">Nilai Akhir</label>
                    <select id="grade" name="grade" class="input">
                        <option value="A" @selected(old('grade', 'A') === 'A')>A</option>
                        <option value="B" @selected(old('grade') === 'B')>B</option>
                        <option value="E" @selected(old('grade') === 'E')>E (tidak selesai sebelum UAS)</option>
                    </select>
                    @error('grade')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="evidence_notes" class="label">Catatan Bukti Kinerja (Opsional)</label>
                <textarea id="evidence_notes" name="evidence_notes" rows="2" maxlength="500" class="input"
                    placeholder="Contoh: SK Pengurus, Sertifikat, Laporan Kegiatan, Surat Rekomendasi Fakultas">{{ old('evidence_notes') }}</textarea>
                @error('evidence_notes')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn btn-primary w-full">Simpan ke Daftar Pengayaan</button>
        </form>
    </section>

    {{-- Daftar MK Pengayaan Mahasiswa --}}
    <section class="lg:col-span-7" aria-labelledby="daftar-pengayaan">
        <h2 id="daftar-pengayaan" class="text-xl">Daftar MK Pengayaan Kamu ({{ $records->count() }} MK)</h2>
        <p class="mt-1 text-sm text-ink-2">Pastikan berkas persyaratan dan bukti kinerja dikonsultasikan ke Dosen Pengampu sebelum pengisian KRS.</p>

        @if ($records->isEmpty())
            <div class="mt-5 rounded-xl border border-dashed border-line-strong bg-surface/50 p-8 text-center text-sm text-ink-2">
                Belum ada kegiatan pengayaan yang dicatat. Mulai cicil dari formulir di samping agar target 20 SKS tidak menumpuk di semester akhir.
            </div>
        @else
            <div class="mt-5 space-y-3">
                @foreach ($records as $r)
                    <article class="rounded-xl border border-line bg-surface p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-medium">{{ $r->course_name }}</h3>
                                    <span class="mono rounded border border-line-strong bg-bg px-2 py-0.5 text-xs">{{ $r->credits }} SKS</span>
                                    @if ($r->status === 'dikonversi')
                                        <span class="rounded border border-ok/40 bg-ok/10 px-2 py-0.5 text-xs font-medium">Terkonversi KRS · Sem {{ $r->conversion_semester }} ({{ $r->grade }})</span>
                                    @elseif ($r->status === 'siap_rekognisi')
                                        <span class="rounded border border-accent/40 bg-accent-soft px-2 py-0.5 text-xs font-medium text-accent">Bukti siap rekognisi</span>
                                    @else
                                        <span class="rounded border border-warn/40 bg-warn/10 px-2 py-0.5 text-xs font-medium">Sedang dicicil</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-ink-2">{{ $r->activity_type }} &middot; Dicicil Sem {{ $r->activity_semester }}</p>
                                <p class="mt-0.5 text-xs text-ink-3">Dosen Pengampu: {{ $r->lecturer }}</p>
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
                            <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-line pt-3">
                                @if ($r->status === 'dicicil')
                                    <form method="POST" action="{{ route('mhs.pengayaan.status', $r) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="siap_rekognisi">
                                        <button type="submit" class="btn btn-quiet btn-sm">Tandai Bukti Siap Rekognisi</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('mhs.pengayaan.status', $r) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="dikonversi">
                                    <input type="number" name="conversion_semester" min="5" max="14" value="{{ max(5, $progress['current_semester']) }}" class="input w-20 py-1 text-xs" aria-label="Semester konversi">
                                    <select name="grade" class="input w-16 py-1 text-xs" aria-label="Nilai">
                                        <option value="A">A</option>
                                        <option value="B">B</option>
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
        <details class="mt-8 rounded-xl border border-line bg-surface p-5">
            <summary class="cursor-pointer font-medium text-accent hover:underline">
                Lihat Katalog Lengkap 22 MK Pengayaan &amp; Jalur Rekognisi Khusus (Hafiz / PKM / KRI)
            </summary>
            <div class="mt-4 space-y-5 text-sm">
                <div class="rounded-lg border border-line bg-bg p-4">
                    <p class="font-medium">Jalur Rekognisi Kompetensi Khusus (Bab 2 Panduan 2025)</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-ink-2">
                        <li><strong>Hafalan Al-Qur'an (Hafiz)</strong>: 1–2 Juz (2 SKS), 4–6 Juz (4 SKS), 7–8 Juz (6 SKS), 9–10 Juz (8 SKS), 11–12 Juz (10 SKS), 13–14 Juz (12 SKS), 15–16 Juz (14 SKS), 16–17 Juz (16 SKS), 18–19 Juz (18 SKS), 20–30 Juz (langsung <strong>20 SKS</strong>).</li>
                        <li><strong>Prestasi PKM</strong>: Lolos Universitas (2–3 SKS), Lolos Pendanaan Regional (8 SKS), Lolos <strong>PIMNAS</strong> (langsung <strong>20 SKS</strong>).</li>
                        <li><strong>Prestasi KRI (Kontes Robot Indonesia)</strong>: Lolos Seleksi (3 SKS), Lolos Regional (8 SKS), Lolos <strong>Nasional</strong> (langsung <strong>20 SKS</strong>).</li>
                    </ul>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-line text-ink-3">
                                <th class="py-2 pr-3">Mata Kuliah</th>
                                <th class="py-2 pr-3">SKS</th>
                                <th class="py-2 pr-3">Dosen Pengampu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($catalog as $item)
                                <tr>
                                    <td class="py-2 pr-3 font-medium">
                                        {{ $item['name'] }}
                                        @if ($item['is_class_based'])
                                            <span class="ml-1 rounded bg-bg px-1.5 py-0.5 text-[10px] text-ink-3">Kelas/Coaching</span>
                                        @endif
                                    </td>
                                    <td class="mono py-2 pr-3">{{ $item['credits'] }}</td>
                                    <td class="py-2 pr-3 text-ink-2">{{ $item['lecturer'] }}</td>
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
    const fields = document.getElementById('conversion-fields');
    fields.classList.toggle('hidden', status !== 'dikonversi');
}

document.addEventListener('DOMContentLoaded', () => {
    updateCourseMeta();
    toggleConversionFields();
});
</script>
@endsection