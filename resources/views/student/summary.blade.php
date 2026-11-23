@extends('layouts.app')

@section('title', 'Ringkasan risiko')

@php
    $done = array_values(array_filter([
        $hasData ? 'impor' : null,
        $latest ? 'risiko' : null,
        $consent?->granted ? 'privasi' : null,
    ]));
    $thresholds = $version?->params ?? \App\Support\RiskModel::DEFAULT_THRESHOLDS;
    $currentCourses = $currentCourses ?? collect();
    $snapshots = $snapshots ?? collect();
@endphp

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="rise text-3xl sm:text-4xl">Ringkasan risiko</h1>
        <p class="rise mt-1 text-ink-2" style="--i: 1">Halo, {{ strtok($user->name, ' ') }}.</p>
    </div>
    @if ($latest)
        <div class="no-print flex flex-wrap gap-2">
            <a href="{{ route('metodologi') }}" class="btn btn-quiet btn-sm">Metodologi</a>
            <form method="POST" action="{{ route('mhs.hitung') }}">
                @csrf
                <button class="btn btn-primary btn-sm" type="submit">Hitung ulang</button>
            </form>
        </div>
    @endif
</div>

@if (! $hasData || ! $consent?->granted)
    <div class="mt-6">
        <x-flow :done="$done" :current="! $hasData ? 'impor' : 'privasi'" />
    </div>
@endif

@if (! $hasData)
    <section class="rise mt-8 rounded-md border border-line-strong bg-surface p-6 sm:p-8" style="--i: 2">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl">Input Mata Kuliah</h2>
                <p class="mt-1 text-sm text-ink-2">Isi nilai semester berjalan untuk langsung melihat skor risiko dan target UAS minimal.</p>
            </div>
            <a href="{{ route('mhs.impor') }}" class="btn btn-quiet btn-sm"><x-icon name="upload-simple" /> Impor CSV / PDF</a>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-md border border-bad/40 bg-bad/10 p-3 text-sm text-bad">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('mhs.tambah-mk') }}" class="mt-6">
            @csrf
            <div class="mb-5 flex flex-wrap items-center gap-3">
                <label for="manual-semester" class="label m-0">Semester</label>
                <input id="manual-semester" type="number" name="semester" min="1" max="14" value="{{ old('semester', 7) }}" class="input h-9 w-24 mono" required>
            </div>

            <div class="overflow-x-auto rounded-md border border-line bg-bg">
                <table class="tbl w-full">
                    <thead>
                        <tr>
                            <th class="px-3 py-2.5">Kode</th>
                            <th class="px-3 py-2.5">Mata Kuliah</th>
                            <th class="px-3 py-2.5 text-center">SKS</th>
                            <th class="px-3 py-2.5 text-center">Hadir (%)</th>
                            <th class="px-3 py-2.5 text-center">UTS</th>
                            <th class="px-3 py-2.5 text-center">Tugas</th>
                            <th class="px-3 py-2.5">Sumber</th>
                            <th class="px-3 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody id="manual-courses-body">
                        <tr>
                            <td class="px-3 py-2.5"><input type="text" name="courses[0][course_code]" value="{{ old('courses.0.course_code', 'IF701') }}" placeholder="IF701" class="input h-9 w-24 mono text-sm" required></td>
                            <td class="px-3 py-2.5"><input type="text" name="courses[0][course_name]" value="{{ old('courses.0.course_name') }}" placeholder="Seminar Capstone Project" class="input h-9 w-full min-w-[13rem] text-sm" required></td>
                            <td class="px-3 py-2.5 text-center"><input type="number" name="courses[0][credits]" min="1" max="6" value="{{ old('courses.0.credits', 3) }}" class="input h-9 w-16 mono text-center text-sm" required></td>
                            <td class="px-3 py-2.5 text-center"><input type="number" step="0.1" name="courses[0][attendance]" min="0" max="100" value="{{ old('courses.0.attendance', 90) }}" class="input h-9 w-20 mono text-center text-sm" required></td>
                            <td class="px-3 py-2.5 text-center"><input type="number" step="0.1" name="courses[0][uts_score]" min="0" max="100" value="{{ old('courses.0.uts_score') }}" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
                            <td class="px-3 py-2.5 text-center"><input type="number" step="0.1" name="courses[0][assignment_score]" min="0" max="100" value="{{ old('courses.0.assignment_score') }}" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
                            <td class="px-3 py-2.5">
                                <select name="courses[0][assignment_source]" class="input h-9 w-32 px-2.5 text-xs">
                                    <option value="mandiri">Mandiri</option>
                                    <option value="olu">OLU</option>
                                    <option value="gdrive">GDrive</option>
                                </select>
                            </td>
                            <td class="px-3 py-2.5 text-right"><button type="button" class="btn btn-quiet btn-sm text-ink-3" onclick="removeManualRow(this)">Hapus</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                <button type="button" class="btn btn-quiet btn-sm" onclick="addManualRow()">+ Tambah Baris</button>
                <button type="submit" class="btn btn-primary">Simpan &amp; Hitung</button>
            </div>
        </form>
    </section>
@else
    <div class="mt-8 grid gap-8 lg:grid-cols-12" style="align-items:start;">
        <div class="rounded-md border border-line bg-surface lg:col-span-8" style="padding:1.75rem;">
            <x-risk-panel :result="$latest" :thresholds="$thresholds" :version="$version?->version" />
        </div>

        <aside class="lg:col-span-4" style="display:flex; flex-direction:column; gap:1rem;">
            <section class="rounded-md border border-line bg-surface p-5" aria-labelledby="per-semester">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="per-semester" class="text-base font-medium">Per semester</h2>
                    <a href="{{ route('mhs.riwayat') }}" class="text-xs font-medium text-accent hover:underline">Riwayat &rarr;</a>
                </div>
                <ul class="mt-3 divide-y divide-line">
                    @foreach ($results->sortByDesc('semester') as $r)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <span class="num text-sm">Sem {{ $r->semester }}</span>
                            <span class="flex items-center gap-2.5">
                                <span class="mono num text-xs text-ink-2">{{ \App\Support\Fmt::num($r->features['ips'] ?? 0) }}</span>
                                <x-risk :level="$r->level" />
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-md border border-line bg-surface p-5" aria-labelledby="status-dpa">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="status-dpa" class="text-base font-medium">Izin DPA</h2>
                    <a href="{{ route('mhs.privasi') }}" class="text-xs font-medium text-accent hover:underline">Atur &rarr;</a>
                </div>
                @if (! $dpa)
                    <p class="mt-2 text-sm text-ink-2">DPA belum ditetapkan.</p>
                @else
                    <p class="mt-2 text-sm font-medium">{{ $dpa->name }}</p>
                    <p class="mt-1 text-xs text-ink-2">
                        @if ($consent?->granted)
                            <span style="color:var(--ok);">Aktif</span> &middot; sejak {{ $consent->changed_at->translatedFormat('j M Y') }}
                        @else
                            Belum diizinkan
                        @endif
                    </p>
                @endif
            </section>

            @if ($reminders->isNotEmpty())
                <section class="rounded-md border border-line bg-surface p-5" aria-labelledby="pengingat">
                    <h2 id="pengingat" class="text-base font-medium">Pengingat</h2>
                    <div class="mt-3" style="display:flex; flex-direction:column; gap:0.75rem;">
                        @foreach ($reminders as $rem)
                            <div class="text-sm">
                                <p class="text-xs text-ink-3">
                                    {{ $rem->created_at->translatedFormat('j M, H:i') }}
                                    @if (in_array($rem->id, $unreadIds, true)) <span class="ml-1 font-medium text-accent">Baru</span> @endif
                                </p>
                                <p class="mt-0.5 text-ink-2">{{ $rem->message }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($snapshots->isNotEmpty())
                <details class="rounded-md border border-line bg-surface p-5">
                    <summary class="cursor-pointer text-sm font-medium text-ink">Jejak cek berkala ({{ $snapshots->count() }})</summary>
                    <ul class="mt-3 divide-y divide-line text-xs">
                        @foreach ($snapshots as $snap)
                            <li class="py-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-medium">{{ $snap->phaseLabel() }}</span>
                                    <span class="mono text-ink-2">{{ \App\Support\Fmt::num($snap->score, 1) }} ({{ $snap->level }})</span>
                                </div>
                                <p class="text-ink-3">{{ $snap->checked_at?->translatedFormat('j M Y, H:i') }} &middot; {{ $snap->components_count }}/4 komponen</p>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </aside>
    </div>

    @if ($currentCourses->isNotEmpty())
        <section class="mt-10 rounded-md border border-line bg-surface" style="padding:1.75rem;" aria-labelledby="input-cepat">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 id="input-cepat" class="text-xl font-medium">Input Cepat &amp; Target UAS (Sem {{ $latest->semester }})</h2>
                    <p class="mt-1 text-sm text-ink-2">Perbarui kehadiran atau nilai untuk melihat proyeksi N &amp; UAS minimal (C: 56 / B: 68).</p>
                </div>
            </div>

            <form method="POST" action="{{ route('mhs.input-cepat') }}" class="mt-5">
                @csrf
                <div class="overflow-x-auto rounded-md border border-line bg-bg">
                    <table class="tbl w-full">
                        <thead>
                            <tr>
                                <th class="min-w-[14rem] px-3 py-3">Mata Kuliah</th>
                                <th class="px-3 py-3 text-center">SKS</th>
                                <th class="px-3 py-3 text-center">Hadir (%)</th>
                                <th class="px-3 py-3 text-center">UTS</th>
                                <th class="px-3 py-3 text-center">Tugas</th>
                                <th class="px-3 py-3">Sumber</th>
                                <th class="px-3 py-3 text-center">UAS</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap">Proyeksi N</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap">UAS Min</th>
                                <th class="px-3 py-3 text-center">Risiko</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($currentCourses as $idx => $c)
                                <tr>
                                    <td class="px-3 py-3">
                                        <input type="hidden" name="courses[{{ $idx }}][id]" value="{{ $c->id }}">
                                        <span class="font-medium">{{ $c->course_name }}</span>
                                        <span class="mono mt-0.5 block text-xs text-ink-3">{{ $c->course_code }}</span>
                                    </td>
                                    <td class="num px-3 py-3 text-center">{{ $c->credits }}</td>
                                    <td class="px-2 py-3 text-center">
                                        <input type="number" step="0.1" min="0" max="100" name="courses[{{ $idx }}][attendance]" value="{{ $c->attendance }}" class="input h-9 w-16 px-2 text-center text-sm mono" required>
                                    </td>
                                    <td class="px-2 py-3 text-center">
                                        <input type="number" step="0.1" min="0" max="100" name="courses[{{ $idx }}][uts_score]" value="{{ $c->uts_score }}" placeholder="-" class="input h-9 w-16 px-2 text-center text-sm mono">
                                    </td>
                                    <td class="px-2 py-3 text-center">
                                        <input type="number" step="0.1" min="0" max="100" name="courses[{{ $idx }}][assignment_score]" value="{{ $c->assignment_score }}" placeholder="-" class="input h-9 w-16 px-2 text-center text-sm mono">
                                    </td>
                                    <td class="px-2 py-3">
                                        <select name="courses[{{ $idx }}][assignment_source]" class="input h-9 w-32 px-2.5 text-xs">
                                            <option value="mandiri" @selected(($c->assignment_source ?? 'mandiri') === 'mandiri')>Mandiri</option>
                                            <option value="olu" @selected($c->assignment_source === 'olu')>OLU</option>
                                            <option value="gdrive" @selected($c->assignment_source === 'gdrive')>GDrive</option>
                                        </select>
                                    </td>
                                    <td class="px-2 py-3 text-center">
                                        <input type="number" step="0.1" min="0" max="100" name="courses[{{ $idx }}][uas_score]" value="{{ $c->uas_score }}" placeholder="-" class="input h-9 w-16 px-2 text-center text-sm mono">
                                    </td>
                                    <td class="num mono px-4 py-3 text-center font-semibold whitespace-nowrap">{{ $c->projected_score !== null ? \App\Support\Fmt::num($c->projected_score, 1) : '-' }}</td>
                                    <td class="num mono px-4 py-3 text-center text-xs whitespace-nowrap">
                                        @if ($c->min_uas_for_c !== null)
                                            C: <strong>{{ \App\Support\Fmt::num($c->min_uas_for_c, 0) }}</strong> / B: {{ \App\Support\Fmt::num($c->min_uas_for_b ?? 0, 0) }}
                                        @else
                                            KHS ({{ $c->grade_input ?? '-' }})
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center whitespace-nowrap"><x-risk :level="$c->course_risk_level ?? 'rendah'" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                    <span class="text-xs text-ink-3">Komponen kosong otomatis dilewati (tidak dihitung 0).</span>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan &amp; Hitung</button>
                </div>
            </form>

            <details class="mt-6 rounded-md border border-line bg-bg p-5">
                <summary class="cursor-pointer text-sm font-medium text-accent hover:underline">+ Tambah Mata Kuliah Baru</summary>
                <form method="POST" action="{{ route('mhs.tambah-mk') }}" class="mt-4">
                    @csrf
                    <div class="mb-4 flex items-center gap-3">
                        <label for="add-semester" class="label m-0 text-sm">Semester</label>
                        <input id="add-semester" type="number" name="semester" min="1" max="14" value="{{ $latest->semester }}" class="input h-9 w-20 mono text-center text-sm" required>
                    </div>
                    <div class="overflow-x-auto rounded-md border border-line bg-surface">
                        <table class="tbl w-full">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2.5">Kode</th>
                                    <th class="px-3 py-2.5">Mata Kuliah</th>
                                    <th class="px-3 py-2.5 text-center">SKS</th>
                                    <th class="px-3 py-2.5 text-center">Hadir (%)</th>
                                    <th class="px-3 py-2.5 text-center">UTS</th>
                                    <th class="px-3 py-2.5 text-center">Tugas</th>
                                    <th class="px-3 py-2.5">Sumber</th>
                                    <th class="px-3 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody id="manual-courses-body">
                                <tr>
                                    <td class="px-3 py-2.5"><input type="text" name="courses[0][course_code]" placeholder="IF702" class="input h-9 w-24 mono text-sm" required></td>
                                    <td class="px-3 py-2.5"><input type="text" name="courses[0][course_name]" placeholder="Nama mata kuliah" class="input h-9 w-full min-w-[13rem] text-sm" required></td>
                                    <td class="px-3 py-2.5 text-center"><input type="number" name="courses[0][credits]" min="1" max="6" value="3" class="input h-9 w-16 mono text-center text-sm" required></td>
                                    <td class="px-3 py-2.5 text-center"><input type="number" step="0.1" name="courses[0][attendance]" min="0" max="100" value="85" class="input h-9 w-20 mono text-center text-sm" required></td>
                                    <td class="px-3 py-2.5 text-center"><input type="number" step="0.1" name="courses[0][uts_score]" min="0" max="100" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
                                    <td class="px-3 py-2.5 text-center"><input type="number" step="0.1" name="courses[0][assignment_score]" min="0" max="100" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
                                    <td class="px-3 py-2.5">
                                        <select name="courses[0][assignment_source]" class="input h-9 w-32 px-2.5 text-xs">
                                            <option value="mandiri">Mandiri</option>
                                            <option value="olu">OLU</option>
                                            <option value="gdrive">GDrive</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2.5 text-right"><button type="button" class="btn btn-quiet btn-sm text-ink-3" onclick="removeManualRow(this)">Hapus</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex flex-wrap justify-between gap-2">
                        <button type="button" class="btn btn-quiet btn-sm" onclick="addManualRow()">+ Tambah Baris</button>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                    </div>
                </form>
            </details>
        </section>
    @endif
@endif

<script>
    let manualRowIdx = 1;
    function addManualRow() {
        const tbody = document.getElementById('manual-courses-body');
        if (!tbody) return;
        const idx = manualRowIdx++;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="px-3 py-2.5"><input type="text" name="courses[${idx}][course_code]" placeholder="IF70${idx + 1}" class="input h-9 w-24 mono text-sm" required></td>
            <td class="px-3 py-2.5"><input type="text" name="courses[${idx}][course_name]" placeholder="Nama mata kuliah" class="input h-9 w-full min-w-[13rem] text-sm" required></td>
            <td class="px-3 py-2.5 text-center"><input type="number" name="courses[${idx}][credits]" min="1" max="6" value="3" class="input h-9 w-16 mono text-center text-sm" required></td>
            <td class="px-3 py-2.5 text-center"><input type="number" step="0.1" name="courses[${idx}][attendance]" min="0" max="100" value="85" class="input h-9 w-20 mono text-center text-sm" required></td>
            <td class="px-3 py-2.5 text-center"><input type="number" step="0.1" name="courses[${idx}][uts_score]" min="0" max="100" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
            <td class="px-3 py-2.5 text-center"><input type="number" step="0.1" name="courses[${idx}][assignment_score]" min="0" max="100" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
            <td class="px-3 py-2.5">
                <select name="courses[${idx}][assignment_source]" class="input h-9 w-32 px-2.5 text-xs">
                    <option value="mandiri">Mandiri</option>
                    <option value="olu">OLU</option>
                    <option value="gdrive">GDrive</option>
                </select>
            </td>
            <td class="px-3 py-2.5 text-right"><button type="button" class="btn btn-quiet btn-sm text-ink-3" onclick="removeManualRow(this)">Hapus</button></td>
        `;
        tbody.appendChild(tr);
    }
    function removeManualRow(btn) {
        const tbody = document.getElementById('manual-courses-body');
        if (tbody && tbody.rows.length > 1) {
            btn.closest('tr').remove();
        }
    }
</script>
@endsection