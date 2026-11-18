@extends('layouts.app')

@section('title', 'Ringkasan risiko')

@php
    $done = array_values(array_filter([
        $hasData ? 'impor' : null,
        $latest ? 'risiko' : null,
        $consent ? 'privasi' : null,
    ]));
    $thresholds = $version?->params ?? \App\Support\RiskModel::DEFAULT_THRESHOLDS;
    $currentCourses = $currentCourses ?? collect();
    $snapshots = $snapshots ?? collect();
@endphp

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="rise text-3xl sm:text-4xl">Ringkasan risiko</h1>
        <p class="rise mt-2 text-ink-2" style="--i: 1">Halo, {{ strtok($user->name, ' ') }}. Ini posisi dan proyeksi akademikmu menurut data terbaru.</p>
    </div>
    @if ($latest)
        <div class="no-print flex flex-wrap gap-2">
            <a href="{{ route('metodologi') }}" class="btn btn-quiet btn-sm">Cara risiko dihitung</a>
            <form method="POST" action="{{ route('mhs.hitung') }}">
                @csrf
                <button class="btn btn-quiet btn-sm" type="submit">Hitung ulang</button>
            </form>
        </div>
    @endif
</div>

<div class="mt-8">
    <x-flow :done="$done" :current="! $hasData ? 'impor' : (! $consent ? 'privasi' : 'risiko')" />
</div>

@if (! $hasData)
    <section class="rise mt-10 rounded-md border border-line-strong bg-surface p-6 sm:p-8" style="--i: 2">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl">Cek Risiko Berkala Langsung</h2>
                <p class="mt-1 max-w-[62ch] text-sm text-ink-2">Langsung masukkan mata kuliah semester berjalan di bawah ini untuk menghitung proyeksi nilai, target UAS minimal (U_min), dan evaluasi AI tanpa harus membuat berkas CSV/Excel terlebih dahulu.</p>
            </div>
            <a href="{{ route('mhs.impor') }}" class="btn btn-quiet btn-sm"><x-icon name="upload-simple" /> Impor Riwayat Lengkap (CSV/PDF)</a>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-md border border-bad/40 bg-bad/10 p-3 text-sm text-bad">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('mhs.tambah-mk') }}" class="mt-6">
            @csrf
            <div class="mb-5 flex flex-wrap items-center gap-3">
                <label for="manual-semester" class="label m-0">Semester berjalan:</label>
                <input id="manual-semester" type="number" name="semester" min="1" max="14" value="{{ old('semester', 7) }}" class="input h-9 w-24 mono" required>
            </div>

            <div class="overflow-x-auto rounded-md border border-line bg-bg">
                <table class="tbl w-full">
                    <thead>
                        <tr>
                            <th class="px-3 py-2.5">Kode MK</th>
                            <th class="px-3 py-2.5">Nama Mata Kuliah</th>
                            <th class="px-3 py-2.5 text-center">SKS</th>
                            <th class="px-3 py-2.5 text-center">Hadir (%)</th>
                            <th class="px-3 py-2.5 text-center">UTS</th>
                            <th class="px-3 py-2.5 text-center">Tugas</th>
                            <th class="px-3 py-2.5">Sumber Tugas</th>
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
                                    <option value="mandiri">Isian sendiri</option>
                                    <option value="olu">Rekap OLU</option>
                                    <option value="gdrive">Rekap GDrive</option>
                                </select>
                            </td>
                            <td class="px-3 py-2.5 text-right"><button type="button" class="btn btn-quiet btn-sm text-ink-3" onclick="removeManualRow(this)">Hapus</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                <button type="button" class="btn btn-quiet btn-sm" onclick="addManualRow()">+ Tambah Baris Mata Kuliah</button>
                <button type="submit" class="btn btn-primary">Simpan &amp; Hitung Risiko Sekarang</button>
            </div>
        </form>
    </section>
@else
    <div class="mt-12 grid gap-12 lg:grid-cols-12">
        <div class="lg:col-span-8">
            <x-risk-panel :result="$latest" :thresholds="$thresholds" :version="$version?->version" />
        </div>

        <aside class="space-y-10 lg:col-span-4">
            <section aria-labelledby="per-semester">
                <h2 id="per-semester" class="text-xl">Per semester</h2>
                <ul class="mt-4 divide-y divide-line">
                    @foreach ($results->sortByDesc('semester') as $r)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <span class="num">Semester {{ $r->semester }}</span>
                            <span class="flex items-center gap-3">
                                <span class="mono num text-ink-2">{{ \App\Support\Fmt::num($r->features['ips'] ?? 0) }}</span>
                                <x-risk :level="$r->level" />
                            </span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('mhs.riwayat') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-accent hover:underline">Riwayat lengkap <x-icon name="arrow-right" size="size-4" /></a>
            </section>

            @if ($snapshots->isNotEmpty())
                <section aria-labelledby="jejak-cek" class="border-t border-line pt-8">
                    <h2 id="jejak-cek" class="text-xl">Jejak cek berkala</h2>
                    <ul class="mt-3 divide-y divide-line text-sm">
                        @foreach ($snapshots as $snap)
                            <li class="py-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-medium">{{ $snap->phaseLabel() }}</span>
                                    <span class="mono text-xs text-ink-2">Skor {{ \App\Support\Fmt::num($snap->score, 1) }} ({{ $snap->level }})</span>
                                </div>
                                <p class="text-xs text-ink-3">{{ $snap->checked_at?->translatedFormat('j M Y, H:i') }} &middot; {{ $snap->components_count }}/4 komponen</p>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section aria-labelledby="status-dpa" class="border-t border-line pt-8">
                <h2 id="status-dpa" class="text-xl">Izin untuk DPA</h2>
                @if (! $dpa)
                    <p class="mt-3 text-ink-2">Belum ada DPA yang ditetapkan untukmu. Sekretariat bisa mengaturnya.</p>
                @else
                    <p class="mt-3">{{ $dpa->name }}</p>
                    <p class="mt-1 text-ink-2">
                        @if ($consent?->granted)
                            Boleh melihat hasilmu sejak {{ $consent->changed_at->translatedFormat('j F Y') }}.
                        @elseif ($consent)
                            Izin dicabut pada {{ $consent->changed_at->translatedFormat('j F Y') }}. DPA tidak bisa melihat hasilmu.
                        @else
                            Belum diizinkan. DPA tidak bisa melihat hasilmu.
                        @endif
                    </p>
                    <a href="{{ route('mhs.privasi') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-accent hover:underline">Atur izin <x-icon name="arrow-right" size="size-4" /></a>
                @endif
            </section>

            <section aria-labelledby="pengingat" class="border-t border-line pt-8">
                <h2 id="pengingat" class="text-xl">Pengingat</h2>
                @forelse ($reminders as $rem)
                    <div class="mt-4">
                        <p class="text-sm text-ink-3">
                            {{ $rem->created_at->translatedFormat('j F Y, H:i') }}
                            @if (in_array($rem->id, $unreadIds, true)) <span class="ml-1 font-medium text-accent">Baru</span> @endif
                        </p>
                        <p class="mt-0.5">{{ $rem->message }}</p>
                    </div>
                @empty
                    <p class="mt-3 text-ink-2">Belum ada pengingat. Pengingat muncul tiap dua minggu atau saat risikomu sedang/tinggi.</p>
                @endforelse
            </section>
        </aside>
    </div>

    @if ($currentCourses->isNotEmpty())
        <section class="mt-14 border-t border-line pt-10" aria-labelledby="input-cepat">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 id="input-cepat" class="text-2xl">Cek Berkala &amp; Target UAS Minimal (Semester {{ $latest->semester }})</h2>
                    <p class="mt-1 max-w-[70ch] text-sm text-ink-2">Perbarui nilai UTS, rata-rata tugas yang sudah dinilai, atau nilai UAS untuk melihat proyeksi nilai sementara (N) dan nilai UAS minimal (U_min) agar lulus C (56) atau B (68).</p>
                </div>
            </div>

            <form method="POST" action="{{ route('mhs.input-cepat') }}" class="mt-6">
                @csrf
                <div class="overflow-x-auto rounded-md border border-line bg-surface/40">
                    <table class="tbl w-full">
                        <thead>
                            <tr>
                                <th class="min-w-[14rem] px-3 py-3">Mata Kuliah</th>
                                <th class="px-3 py-3 text-center">SKS</th>
                                <th class="px-3 py-3 text-center">Hadir (%)</th>
                                <th class="px-3 py-3 text-center">UTS (25%)</th>
                                <th class="px-3 py-3 text-center">Tugas (25%)</th>
                                <th class="px-3 py-3">Sumber Tugas</th>
                                <th class="px-3 py-3 text-center">UAS (40%)</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap">Proyeksi N</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap">U_min (C / B)</th>
                                <th class="px-3 py-3 text-center">Risiko MK</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($currentCourses as $idx => $c)
                                <tr>
                                    <td class="px-3 py-3">
                                        <input type="hidden" name="courses[{{ $idx }}][id]" value="{{ $c->id }}">
                                        <span class="font-medium">{{ $c->course_name }}</span>
                                        <span class="mono mt-0.5 block text-xs text-ink-3">{{ $c->course_code }} &middot; {{ $c->trustLabel() }}</span>
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
                                            <option value="mandiri" @selected(($c->assignment_source ?? 'mandiri') === 'mandiri')>Isian sendiri</option>
                                            <option value="olu" @selected($c->assignment_source === 'olu')>Rekap OLU</option>
                                            <option value="gdrive" @selected($c->assignment_source === 'gdrive')>Rekap GDrive</option>
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
                                            Resmi ({{ $c->grade_input ?? '-' }})
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center whitespace-nowrap"><x-risk :level="$c->course_risk_level ?? 'rendah'" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                    <p class="text-xs text-ink-3">Mata kuliah tanpa tugas boleh dikosongkan (dilewati dan tidak dihitung nol).</p>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan &amp; Hitung Proyeksi</button>
                </div>
            </form>

            <details class="mt-6 rounded-md border border-line bg-surface p-5">
                <summary class="cursor-pointer text-sm font-medium text-accent hover:underline">+ Tambah Mata Kuliah Baru Tanpa Impor File</summary>
                <form method="POST" action="{{ route('mhs.tambah-mk') }}" class="mt-4">
                    @csrf
                    <div class="mb-4 flex items-center gap-3">
                        <label for="add-semester" class="label m-0 text-sm">Semester:</label>
                        <input id="add-semester" type="number" name="semester" min="1" max="14" value="{{ $latest->semester }}" class="input h-9 w-20 mono text-center text-sm" required>
                    </div>
                    <div class="overflow-x-auto rounded-md border border-line bg-bg">
                        <table class="tbl w-full">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2.5">Kode MK</th>
                                    <th class="px-3 py-2.5">Nama Mata Kuliah</th>
                                    <th class="px-3 py-2.5 text-center">SKS</th>
                                    <th class="px-3 py-2.5 text-center">Hadir (%)</th>
                                    <th class="px-3 py-2.5 text-center">UTS</th>
                                    <th class="px-3 py-2.5 text-center">Tugas</th>
                                    <th class="px-3 py-2.5">Sumber Tugas</th>
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
                                            <option value="mandiri">Isian sendiri</option>
                                            <option value="olu">Rekap OLU</option>
                                            <option value="gdrive">Rekap GDrive</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2.5 text-right"><button type="button" class="btn btn-quiet btn-sm text-ink-3" onclick="removeManualRow(this)">Hapus</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex flex-wrap justify-between gap-2">
                        <button type="button" class="btn btn-quiet btn-sm" onclick="addManualRow()">+ Tambah Baris</button>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan Mata Kuliah</button>
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
                    <option value="mandiri">Isian sendiri</option>
                    <option value="olu">Rekap OLU</option>
                    <option value="gdrive">Rekap GDrive</option>
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