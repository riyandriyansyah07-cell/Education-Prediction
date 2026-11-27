@extends('layouts.app')

@section('title', 'Ringkasan risiko')

@php
    $done = array_values(array_filter([
        $hasData ? 'impor' : null,
        $latest ? 'risiko' : null,
        $dpa ? 'privasi' : null,
    ]));
    $thresholds = $version?->params ?? \App\Support\RiskModel::DEFAULT_THRESHOLDS;
    $currentCourses = $currentCourses ?? collect();
    $snapshots = $snapshots ?? collect();
    $latestSemester = $latestSemester ?? $results->last()?->semester;
@endphp

@section('content')
<style>
    .sum-tbl .sum-inp {
        min-height: 36px !important;
        height: 36px;
        width: 4.1rem;
        padding: 0.25rem 0.35rem !important;
        text-align: center;
        font-size: 0.84rem !important;
    }
    @media (max-width: 860px) {
        .sum-card-wrap {
            padding: 1.1rem !important;
        }
        .sum-tbl-box {
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            overflow: visible !important;
        }
        .sum-tbl thead {
            display: none;
        }
        .sum-tbl, .sum-tbl tbody {
            display: block;
            width: 100%;
        }
        .sum-tbl tbody tr {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 0.75rem 0.55rem;
            padding: 1.05rem;
            margin-bottom: 0.95rem;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: var(--bg);
        }
        .sum-tbl tbody tr:last-child {
            margin-bottom: 0;
        }
        .sum-tbl tbody td {
            display: block;
            padding: 0 !important;
            border: none !important;
            text-align: left !important;
            min-width: 0;
        }
        .sum-tbl tbody td.sum-td-course {
            grid-column: 1 / -1;
            order: 1;
            line-height: 1.4;
        }
        .sum-tbl tbody td.sum-td-risk {
            grid-column: 1 / -1;
            order: 2;
            justify-self: start;
            padding-bottom: 0.25rem !important;
        }
        .sum-tbl tbody td.sum-td-inp {
            order: 3;
            grid-column: span 2;
        }
        .sum-tbl tbody td.sum-td-inp-wide {
            order: 3;
            grid-column: span 3;
        }
        .sum-tbl tbody td.sum-td-inp::before {
            content: attr(data-label);
            display: block;
            font-size: 10.5px;
            font-weight: 600;
            color: var(--ink-3);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 0.32rem;
            text-align: center;
        }
        .sum-tbl tbody td.sum-td-inp .sum-inp {
            width: 100% !important;
            min-height: 42px !important;
            height: 42px;
            font-size: 15px !important;
        }
        .sum-tbl tbody td.sum-td-meta {
            order: 4;
            grid-column: span 2;
            padding-top: 0.65rem !important;
            margin-top: 0.25rem;
            border-top: 1px solid var(--line) !important;
            font-size: 12px !important;
        }
        .sum-tbl tbody td.sum-td-meta::before {
            content: attr(data-label) " ";
            font-size: 10.5px;
            color: var(--ink-3);
            font-weight: 500;
        }
        .sum-tbl tbody td.sum-td-uasmin {
            grid-column: span 2;
            text-align: right !important;
        }
        /* Responsive Card untuk Tabel Tambah Mata Kuliah Baru di HP */
        .addmk-box {
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            overflow: visible !important;
        }
        .addmk-tbl thead { display: none; }
        .addmk-tbl, .addmk-tbl tbody { display: block; width: 100%; }
        .addmk-tbl tbody tr {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem 0.55rem;
            padding: 1rem;
            margin-bottom: 0.9rem;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: var(--surface);
        }
        .addmk-tbl tbody tr:last-child { margin-bottom: 0; }
        .addmk-tbl tbody td {
            display: block;
            padding: 0 !important;
            border: none !important;
            text-align: left !important;
            min-width: 0;
        }
        .addmk-tbl tbody td[data-label]::before {
            content: attr(data-label);
            display: block;
            font-size: 10.5px;
            font-weight: 600;
            color: var(--ink-3);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 0.3rem;
        }
        .addmk-tbl tbody td .input {
            width: 100% !important;
            min-width: 0 !important;
            min-height: 42px !important;
            height: 42px;
            font-size: 15px !important;
        }
        .addmk-tbl tbody td.addmk-code { grid-column: 1 / 5; }
        .addmk-tbl tbody td.addmk-name { grid-column: 1 / 5; }
        .addmk-tbl tbody td.addmk-num { grid-column: span 2; }
        .addmk-tbl tbody td.addmk-act {
            grid-column: 1 / 5;
            padding-top: 0.5rem !important;
            border-top: 1px solid var(--line) !important;
            text-align: right !important;
        }
    }
</style>

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
                <input type="hidden" name="semester" value="{{ $latest->semester }}">
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
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl">Input Data Semester</h2>
                <p class="mt-1 text-sm text-ink-2">Masukkan data mata kuliah semester berjalan, atau impor langsung dari file KHS.</p>
            </div>
            <a href="{{ route('mhs.impor') }}" class="btn btn-primary"><x-icon name="upload-simple" /> Impor KHS (PDF / CSV / XLSX)</a>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-md border border-bad/40 bg-bad/10 p-3 text-sm text-bad">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('mhs.tambah-mk') }}" class="mt-6">
            @csrf
            <div class="mb-5 flex flex-wrap items-center gap-3">
                <label for="manual-semester" class="label" style="margin:0;">Semester</label>
                <input id="manual-semester" type="number" name="semester" min="1" max="14" value="{{ old('semester', 7) }}" class="input mono text-center" style="width:5.5rem;" required>
            </div>

            <div class="addmk-box overflow-x-auto rounded-md border border-line bg-bg">
                <table class="tbl addmk-tbl w-full">
                    <thead>
                        <tr>
                            <th class="px-3 py-2.5">Kode</th>
                            <th class="px-3 py-2.5">Mata Kuliah</th>
                            <th class="px-3 py-2.5 text-center">SKS</th>
                            <th class="px-3 py-2.5 text-center">Hadir (%)</th>
                            <th class="px-3 py-2.5 text-center">Keaktifan</th>
                            <th class="px-3 py-2.5 text-center">Tugas</th>
                            <th class="px-3 py-2.5 text-center">UTS</th>
                            <th class="px-3 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody id="manual-courses-body">
                        <tr>
                            <td class="addmk-code px-3 py-2.5" data-label="Kode MK">
                                <input type="hidden" name="courses[0][assignment_source]" value="mandiri">
                                <input type="text" name="courses[0][course_code]" value="{{ old('courses.0.course_code', 'IF701') }}" placeholder="IF701" class="input h-9 w-24 mono text-sm" required>
                            </td>
                            <td class="addmk-name px-3 py-2.5" data-label="Nama Mata Kuliah"><input type="text" name="courses[0][course_name]" value="{{ old('courses.0.course_name') }}" placeholder="Seminar Capstone Project" class="input h-9 w-full min-w-[12rem] text-sm" required></td>
                            <td class="addmk-num px-3 py-2.5 text-center" data-label="SKS"><input type="number" name="courses[0][credits]" min="1" max="6" value="{{ old('courses.0.credits', 3) }}" class="input h-9 w-16 mono text-center text-sm" required></td>
                            <td class="addmk-num px-3 py-2.5 text-center" data-label="Hadir (%)"><input type="number" step="0.1" name="courses[0][attendance]" min="0" max="100" value="{{ old('courses.0.attendance', 90) }}" class="input h-9 w-20 mono text-center text-sm" required></td>
                            <td class="addmk-num px-3 py-2.5 text-center" data-label="Keaktifan"><input type="number" step="0.1" name="courses[0][activity_score]" min="0" max="100" value="{{ old('courses.0.activity_score') }}" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
                            <td class="addmk-num px-3 py-2.5 text-center" data-label="Tugas"><input type="number" step="0.1" name="courses[0][assignment_score]" min="0" max="100" value="{{ old('courses.0.assignment_score') }}" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
                            <td class="addmk-num px-3 py-2.5 text-center" data-label="UTS"><input type="number" step="0.1" name="courses[0][uts_score]" min="0" max="100" value="{{ old('courses.0.uts_score') }}" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
                            <td class="addmk-act px-3 py-2.5 text-right"><button type="button" class="btn btn-quiet btn-sm text-ink-3" onclick="removeManualRow(this)">Hapus</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                <button type="button" class="btn-add-soft" onclick="addManualRow()"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 4.5v11M4.5 10h11"/></svg><span>Tambah Baris</span></button>
                <button type="submit" class="btn btn-primary">Simpan &amp; Hitung</button>
            </div>
        </form>
    </section>
@else
    <div class="mt-8 grid gap-8 lg:grid-cols-12" style="align-items:start;">
        <div class="rounded-md border border-line bg-surface lg:col-span-8" style="padding:1.75rem;">
            @if ($latestSemester && $latest->semester !== $latestSemester)
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-md border border-line bg-bg px-3 py-2 text-xs text-ink-2">
                    <span>Menampilkan arsip <strong>Semester {{ $latest->semester }}</strong>.</span>
                    <a href="{{ route('mhs.ringkasan') }}" class="font-medium text-accent hover:underline">Kembali ke Sem {{ $latestSemester }} (terbaru) &rarr;</a>
                </div>
            @endif
            <x-risk-panel :result="$latest" :thresholds="$thresholds" :version="$version?->version" />
        </div>

        <aside class="lg:col-span-4" style="display:flex; flex-direction:column; gap:1rem;">
            <section class="rounded-md border border-line bg-surface p-5" aria-labelledby="per-semester">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="per-semester" class="text-base font-medium">Per semester</h2>
                    <a href="{{ route('mhs.riwayat') }}" class="text-xs font-medium text-accent hover:underline">Riwayat &rarr;</a>
                </div>
                <p class="mt-1 text-xs text-ink-3">Klik semester untuk melihat hasil &amp; rinciannya.</p>
                <ul class="mt-3" style="display:flex; flex-direction:column; gap:4px;">
                    @foreach ($results->sortByDesc('semester') as $r)
                        @php $isActiveSem = $r->semester === $latest->semester; @endphp
                        <li>
                            <a href="{{ route('mhs.ringkasan', ['semester' => $r->semester]) }}"
                               class="flex items-center justify-between gap-3 text-ink"
                               style="padding:0.55rem 0.65rem; border-radius:8px; text-decoration:none; border:1px solid {{ $isActiveSem ? 'var(--accent)' : 'transparent' }}; background:{{ $isActiveSem ? 'var(--accent-soft)' : 'transparent' }}; transition:background-color 140ms ease;">
                                <span class="num text-sm {{ $isActiveSem ? 'font-semibold text-accent' : '' }}">
                                    Sem {{ $r->semester }}
                                </span>
                                <span class="flex items-center gap-2.5">
                                    <span class="mono num text-xs text-ink-2">{{ \App\Support\Fmt::num($r->features['ips'] ?? 0) }}</span>
                                    <x-risk :level="$r->level" />
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-md border border-line bg-surface p-5" aria-labelledby="status-dpa">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="status-dpa" class="text-base font-medium">DPA Pendamping</h2>
                    <a href="{{ route('mhs.privasi') }}" class="text-xs font-medium text-accent hover:underline">Privasi &rarr;</a>
                </div>
                @if (! $dpa)
                    <p class="mt-2 text-sm text-ink-2">DPA belum ditetapkan.</p>
                @else
                    <p class="mt-2 text-sm font-medium">{{ $dpa->name }}</p>
                    <p class="mt-1 text-xs text-ink-2">
                        <span style="color:var(--ok);font-weight:500;">Aktif</span> &middot; Dosen Pembimbing Akademik
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
                <details class="ui-acc" style="padding:1rem 1.15rem;">
                    <summary class="ui-acc-head">
                        <span class="ui-acc-left">
                            <span class="ui-acc-icon"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="M10 6.5v3.8l2.5 1.5"/></svg></span>
                            <span>
                                <span class="ui-acc-title">Jejak cek berkala Sem {{ $latest->semester }}</span>
                                <span class="ui-acc-sub">{{ $snapshots->count() }} riwayat pengecekan tersimpan</span>
                            </span>
                        </span>
                        <svg class="ui-acc-chev" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 7.5L10 12.5L15 7.5"/></svg>
                    </summary>
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
        <section class="sum-card-wrap mt-8 bg-surface" style="padding:1.5rem 1.65rem;" aria-labelledby="input-cepat">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 id="input-cepat" class="text-xl font-medium">Rincian Nilai &amp; Target UAS (Sem {{ $latest->semester }})</h2>
                    <p class="mt-1 text-sm text-ink-2">Perbarui kehadiran atau nilai mata kuliah untuk menghitung proyeksi N &amp; target UAS minimal (C: 56 / B: 68 / A: 80).</p>
                </div>
            </div>

            <form method="POST" action="{{ route('mhs.input-cepat') }}" class="mt-5">
                @csrf
                <input type="hidden" name="semester" value="{{ $latest->semester }}">
                <div class="sum-tbl-box overflow-x-auto">
                    <table class="tbl sum-tbl w-full">
                        <thead>
                            <tr>
                                <th class="px-3 py-3">Mata Kuliah</th>
                                <th class="px-2 py-3 text-center">SKS</th>
                                <th class="px-2 py-3 text-center">Hadir (%)</th>
                                <th class="px-2 py-3 text-center">Keaktifan</th>
                                <th class="px-2 py-3 text-center">Tugas</th>
                                <th class="px-2 py-3 text-center">UTS</th>
                                <th class="px-2 py-3 text-center">UAS</th>
                                <th class="px-3 py-3 text-center whitespace-nowrap">Nilai N</th>
                                <th class="px-3 py-3 text-center whitespace-nowrap">UAS Min</th>
                                <th class="px-3 py-3 text-center">Risiko</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($currentCourses as $idx => $c)
                                @php $isEnrichment = \App\Support\EnrichmentCatalog::matchCourseName((string) $c->course_name) !== null; @endphp
                                <tr>
                                    <td class="sum-td-course px-3 py-3">
                                        <input type="hidden" name="courses[{{ $idx }}][id]" value="{{ $c->id }}">
                                        <input type="hidden" name="courses[{{ $idx }}][assignment_source]" value="{{ $c->assignment_source ?? 'mandiri' }}">
                                        <span class="font-medium">{{ $c->course_name }}</span>
                                        <span class="mono mt-0.5 flex flex-wrap items-center text-xs text-ink-3" style="gap:6px;">
                                            <span>{{ $c->course_code }}</span>
                                            @if ($isEnrichment)
                                                <span class="rounded border border-accent/40 bg-accent-soft px-1.5 py-0.5 text-accent" style="font-size:10px;">Pengayaan</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td class="sum-td-meta num px-2 py-3 text-center" data-label="SKS">{{ $c->credits }}</td>
                                    <td class="sum-td-inp px-2 py-3 text-center" data-label="Hadir %">
                                        <input type="number" step="0.1" min="0" max="100" name="courses[{{ $idx }}][attendance]" value="{{ $c->attendance }}" class="input sum-inp mono" required>
                                    </td>
                                    <td class="sum-td-inp px-2 py-3 text-center" data-label="Keaktifan">
                                        <input type="number" step="0.1" min="0" max="100" name="courses[{{ $idx }}][activity_score]" value="{{ $c->activity_score }}" placeholder="-" class="input sum-inp mono">
                                    </td>
                                    <td class="sum-td-inp px-2 py-3 text-center" data-label="Tugas">
                                        <input type="number" step="0.1" min="0" max="100" name="courses[{{ $idx }}][assignment_score]" value="{{ $c->assignment_score }}" placeholder="-" class="input sum-inp mono">
                                    </td>
                                    <td class="sum-td-inp sum-td-inp-wide px-2 py-3 text-center" data-label="UTS">
                                        <input type="number" step="0.1" min="0" max="100" name="courses[{{ $idx }}][uts_score]" value="{{ $c->uts_score }}" placeholder="-" class="input sum-inp mono">
                                    </td>
                                    <td class="sum-td-inp sum-td-inp-wide px-2 py-3 text-center" data-label="UAS">
                                        <input type="number" step="0.1" min="0" max="100" name="courses[{{ $idx }}][uas_score]" value="{{ $c->uas_score }}" placeholder="-" class="input sum-inp mono">
                                    </td>
                                    <td class="sum-td-meta num mono px-3 py-3 text-center font-semibold whitespace-nowrap" data-label="Nilai N">{{ $c->projected_score !== null ? \App\Support\Fmt::num($c->projected_score, 1) : '-' }}</td>
                                    <td class="sum-td-meta sum-td-uasmin num mono px-3 py-3 text-center text-xs whitespace-nowrap" data-label="UAS Min">
                                        @if ($c->min_uas_for_c !== null)
                                            @php $minUasA = round((float) ($c->min_uas_for_b ?? 0) + 30.0, 2); @endphp
                                            C: <strong>{{ \App\Support\Fmt::num($c->min_uas_for_c, 0) }}</strong> / B: {{ \App\Support\Fmt::num($c->min_uas_for_b ?? 0, 0) }} / A: <strong style="color:var(--ok);">{{ $minUasA > 100 ? '>100' : \App\Support\Fmt::num($minUasA, 0) }}</strong>
                                        @else
                                            KHS ({{ $c->grade_input ?? '-' }})
                                        @endif
                                    </td>
                                    <td class="sum-td-risk px-3 py-3 text-center whitespace-nowrap"><x-risk :level="$c->course_risk_level ?? 'rendah'" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                    <span class="text-xs text-ink-3">Komponen kosong dilewati (tidak dihitung 0). MK Pengayaan non-kelas bebas syarat absen 75%.</span>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan &amp; Hitung</button>
                </div>
            </form>

            <details class="ui-acc is-add mt-6" style="padding:1rem 1.15rem;">
                <summary class="ui-acc-head">
                    <span class="ui-acc-left">
                        <span class="ui-acc-icon"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 4.5v11M4.5 10h11"/></svg></span>
                        <span>
                            <span class="ui-acc-title">Tambah Mata Kuliah Baru</span>
                            <span class="ui-acc-sub">Tambahkan mata kuliah manual untuk menghitung proyeksi nilai &amp; target UAS</span>
                        </span>
                    </span>
                    <svg class="ui-acc-chev" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 7.5L10 12.5L15 7.5"/></svg>
                </summary>
                <form method="POST" action="{{ route('mhs.tambah-mk') }}" class="mt-4">
                    @csrf
                    <div class="mb-4 flex items-center gap-3">
                        <label for="add-semester" class="label text-sm" style="margin:0;">Semester</label>
                        <input id="add-semester" type="number" name="semester" min="1" max="14" value="{{ $latest->semester }}" class="input mono text-center" style="width:5.5rem;" required>
                    </div>
                    <div class="addmk-box overflow-x-auto rounded-md border border-line bg-surface">
                        <table class="tbl addmk-tbl w-full">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2.5">Kode</th>
                                    <th class="px-3 py-2.5">Mata Kuliah</th>
                                    <th class="px-3 py-2.5 text-center">SKS</th>
                                    <th class="px-3 py-2.5 text-center">Hadir (%)</th>
                                    <th class="px-3 py-2.5 text-center">Keaktifan</th>
                                    <th class="px-3 py-2.5 text-center">Tugas</th>
                                    <th class="px-3 py-2.5 text-center">UTS</th>
                                    <th class="px-3 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody id="manual-courses-body">
                                <tr>
                                    <td class="addmk-code px-3 py-2.5" data-label="Kode MK">
                                        <input type="hidden" name="courses[0][assignment_source]" value="mandiri">
                                        <input type="text" name="courses[0][course_code]" placeholder="IF702" class="input h-9 w-24 mono text-sm" required>
                                    </td>
                                    <td class="addmk-name px-3 py-2.5" data-label="Nama Mata Kuliah"><input type="text" name="courses[0][course_name]" placeholder="Nama mata kuliah" class="input h-9 w-full min-w-[12rem] text-sm" required></td>
                                    <td class="addmk-num px-3 py-2.5 text-center" data-label="SKS"><input type="number" name="courses[0][credits]" min="1" max="6" value="3" class="input h-9 w-16 mono text-center text-sm" required></td>
                                    <td class="addmk-num px-3 py-2.5 text-center" data-label="Hadir (%)"><input type="number" step="0.1" name="courses[0][attendance]" min="0" max="100" value="85" class="input h-9 w-20 mono text-center text-sm" required></td>
                                    <td class="addmk-num px-3 py-2.5 text-center" data-label="Keaktifan"><input type="number" step="0.1" name="courses[0][activity_score]" min="0" max="100" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
                                    <td class="addmk-num px-3 py-2.5 text-center" data-label="Tugas"><input type="number" step="0.1" name="courses[0][assignment_score]" min="0" max="100" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
                                    <td class="addmk-num px-3 py-2.5 text-center" data-label="UTS"><input type="number" step="0.1" name="courses[0][uts_score]" min="0" max="100" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
                                    <td class="addmk-act px-3 py-2.5 text-right"><button type="button" class="btn btn-quiet btn-sm text-ink-3" onclick="removeManualRow(this)">Hapus</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex flex-wrap justify-between gap-2">
                        <button type="button" class="btn-add-soft" onclick="addManualRow()"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 4.5v11M4.5 10h11"/></svg><span>Tambah Baris</span></button>
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
            <td class="addmk-code px-3 py-2.5" data-label="Kode MK">
                <input type="hidden" name="courses[${idx}][assignment_source]" value="mandiri">
                <input type="text" name="courses[${idx}][course_code]" placeholder="IF70${idx + 1}" class="input h-9 w-24 mono text-sm" required>
            </td>
            <td class="addmk-name px-3 py-2.5" data-label="Nama Mata Kuliah"><input type="text" name="courses[${idx}][course_name]" placeholder="Nama mata kuliah" class="input h-9 w-full min-w-[12rem] text-sm" required></td>
            <td class="addmk-num px-3 py-2.5 text-center" data-label="SKS"><input type="number" name="courses[${idx}][credits]" min="1" max="6" value="3" class="input h-9 w-16 mono text-center text-sm" required></td>
            <td class="addmk-num px-3 py-2.5 text-center" data-label="Hadir (%)"><input type="number" step="0.1" name="courses[${idx}][attendance]" min="0" max="100" value="85" class="input h-9 w-20 mono text-center text-sm" required></td>
            <td class="addmk-num px-3 py-2.5 text-center" data-label="Keaktifan"><input type="number" step="0.1" name="courses[${idx}][activity_score]" min="0" max="100" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
            <td class="addmk-num px-3 py-2.5 text-center" data-label="Tugas"><input type="number" step="0.1" name="courses[${idx}][assignment_score]" min="0" max="100" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
            <td class="addmk-num px-3 py-2.5 text-center" data-label="UTS"><input type="number" step="0.1" name="courses[${idx}][uts_score]" min="0" max="100" placeholder="-" class="input h-9 w-20 mono text-center text-sm"></td>
            <td class="addmk-act px-3 py-2.5 text-right"><button type="button" class="btn btn-quiet btn-sm text-ink-3" onclick="removeManualRow(this)">Hapus</button></td>
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