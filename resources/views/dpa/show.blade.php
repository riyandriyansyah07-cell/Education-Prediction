@extends('layouts.app')

@section('title', $student->name)

@php
    $setting = $setting ?? new \App\Models\StudentSetting(['follow_up_status' => 'belum_dihubungi']);
    $courses = $courses ?? collect();
    $notes = $notes ?? collect();
    $snapshots = $snapshots ?? collect();
    $enrichmentRecords = $enrichmentRecords ?? collect();
    $latestSemester = $latestSemester ?? $results->last()?->semester;
    $enrichment = $enrichment ?? \App\Support\EnrichmentCatalog::evaluateProgress($enrichmentRecords, (int) ($latestSemester ?? 1));
@endphp

@section('content')
<a href="{{ route('dpa.bimbingan') }}" class="no-print inline-flex items-center gap-1 text-sm font-medium text-accent hover:underline"><x-icon name="arrow-right" class="rotate-180" size="size-4" /> Daftar bimbingan</a>
<div class="mt-4 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="rise text-3xl sm:text-4xl">{{ $student->name }}</h1>
        <p class="rise mono mt-1 text-ink-2" style="--i: 1">NIM: {{ $student->username }} &middot; {{ $setting->followUpLabel() }}</p>
    </div>
</div>

@if (! $latest)
    <p class="mt-10 max-w-xl text-ink-2">Mahasiswa ini belum mengimpor data, jadi belum ada hasil risiko.</p>
@else
    @php $version = $latest->modelVersion; $thresholds = $version?->params ?? \App\Support\RiskModel::DEFAULT_THRESHOLDS; @endphp
    <div class="mt-8 grid gap-8 lg:grid-cols-12" style="align-items:start;">
        <div class="lg:col-span-8" style="display:flex; flex-direction:column; gap:1.5rem;">
            <div class="rounded-md border border-line bg-surface" style="padding:1.75rem;">
                @if ($latestSemester && $latest->semester !== $latestSemester)
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-md border border-line bg-bg px-3 py-2 text-xs text-ink-2">
                        <span>Menampilkan arsip <strong>Semester {{ $latest->semester }}</strong>.</span>
                        <a href="{{ route('dpa.bimbingan.show', $student) }}" class="font-medium text-accent hover:underline">Kembali ke Sem {{ $latestSemester }} (terbaru) &rarr;</a>
                    </div>
                @endif
                <x-risk-panel :result="$latest" :thresholds="$thresholds" :version="$version?->version" audience="dpa" />
            </div>

            @if ($courses->isNotEmpty())
                <section class="rounded-md border border-line bg-surface" style="padding:1.5rem;" aria-labelledby="proyeksi-mk-dpa">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="proyeksi-mk-dpa" class="text-lg font-medium">Rincian Nilai &amp; Target UAS (Sem {{ $latest->semester }})</h2>
                        <span class="text-xs text-ink-3">{{ $courses->count() }} mata kuliah</span>
                    </div>
                    <div class="mt-4 overflow-x-auto rounded-md border border-line bg-bg">
                        <table class="tbl w-full">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2.5">Mata Kuliah</th>
                                    <th class="px-2 py-2.5 text-center">SKS</th>
                                    <th class="px-2 py-2.5 text-center">Hadir</th>
                                    <th class="px-2 py-2.5 text-center whitespace-nowrap">UTS / Tugas / UAS</th>
                                    <th class="px-2 py-2.5 text-center whitespace-nowrap">Nilai N</th>
                                    <th class="px-2 py-2.5 text-center whitespace-nowrap">UAS Min</th>
                                    <th class="px-3 py-2.5 text-center">Risiko</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($courses as $c)
                                    @php $isEnrichment = \App\Support\EnrichmentCatalog::matchCourseName((string) $c->course_name) !== null; @endphp
                                    <tr>
                                        <td class="px-3 py-2.5">
                                            <span class="font-medium">{{ $c->course_name }}</span>
                                            <span class="mono mt-0.5 flex flex-wrap items-center text-xs text-ink-3" style="gap:6px;">
                                                <span>{{ $c->course_code }}</span>
                                                @if ($isEnrichment)
                                                    <span class="rounded border border-accent/40 bg-accent-soft px-1.5 py-0.5 text-accent" style="font-size:10px;">Pengayaan</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td class="num px-2 py-2.5 text-center">{{ $c->credits }}</td>
                                        <td class="num px-2 py-2.5 text-center whitespace-nowrap">{{ \App\Support\Fmt::pct($c->attendance) }}</td>
                                        <td class="mono px-2 py-2.5 text-center text-xs whitespace-nowrap">{{ $c->uts_score ?? '-' }} / {{ $c->assignment_score ?? '-' }} / {{ $c->uas_score ?? ($c->grade_input ?? '-') }}</td>
                                        <td class="num mono px-2 py-2.5 text-center font-semibold whitespace-nowrap">{{ $c->projected_score !== null ? \App\Support\Fmt::num($c->projected_score, 1) : '-' }}</td>
                                        <td class="mono px-2 py-2.5 text-center text-xs whitespace-nowrap">
                                            @if ($c->min_uas_for_c !== null)
                                                C: <strong>{{ \App\Support\Fmt::num($c->min_uas_for_c, 0) }}</strong> / B: {{ \App\Support\Fmt::num($c->min_uas_for_b ?? 0, 0) }}
                                            @else
                                                KHS ({{ $c->grade_input ?? '-' }})
                                            @endif
                                        </td>
                                        <td class="px-3 py-2.5 text-center whitespace-nowrap"><x-risk :level="$c->course_risk_level ?? 'rendah'" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            <section class="risk-{{ $enrichment['risk_level'] }} rounded-md border border-line bg-surface" style="padding:1.5rem;" aria-labelledby="pengayaan-dpa">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="pengayaan-dpa" class="text-lg font-medium">Progres 20 SKS Pengayaan &amp; PKM</h2>
                    <span class="mono text-sm font-medium">{{ $enrichment['converted_sks'] }} / {{ $enrichment['target_sks'] }} SKS &middot; {{ $enrichment['pkm_label'] }}</span>
                </div>
                <p class="mt-1.5 text-sm text-ink-2">{{ $enrichment['status_message'] }}</p>

                <div class="mt-4 grid gap-3 sm:grid-cols-3 text-xs">
                    <div class="rounded-md border border-line bg-bg p-3">
                        <p class="text-ink-3">Terkonversi KRS</p>
                        <p class="mono mt-1 text-lg font-medium">{{ $enrichment['converted_sks'] }} SKS</p>
                    </div>
                    <div class="rounded-md border border-line bg-bg p-3">
                        <p class="text-ink-3">Siap Rekognisi</p>
                        <p class="mono mt-1 text-lg font-medium">{{ $enrichment['ready_sks'] }} SKS</p>
                    </div>
                    <div class="rounded-md border border-line bg-bg p-3">
                        <p class="text-ink-3">Sedang Dicicil</p>
                        <p class="mono mt-1 text-lg font-medium">{{ $enrichment['in_progress_sks'] }} SKS</p>
                    </div>
                </div>

                @if ($enrichmentRecords->isNotEmpty())
                    <ul class="mt-4 divide-y divide-line rounded-md border border-line bg-bg px-4 text-xs">
                        @foreach ($enrichmentRecords as $er)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2.5">
                                <span>
                                    <strong class="font-medium">{{ $er->course_name }}</strong> ({{ $er->credits }} SKS)
                                    <span class="text-ink-3">&middot; {{ $er->activity_type }}</span>
                                </span>
                                <span class="text-ink-2">
                                    {{ $er->statusLabel() }}@if ($er->status === 'dikonversi') &middot; Sem {{ $er->conversion_semester }} ({{ $er->grade }})@endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="rounded-md border border-line bg-surface" style="padding:1.5rem;" aria-labelledby="catatan-bimbingan">
                <h2 id="catatan-bimbingan" class="text-lg font-medium">Catatan Bimbingan</h2>
                <form method="POST" action="{{ route('dpa.bimbingan.catatan', $student) }}" class="mt-4 space-y-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="guidance_date" class="label">Tanggal bimbingan</label>
                            <input id="guidance_date" name="guidance_date" type="date" value="{{ old('guidance_date', now()->toDateString()) }}" class="input" required>
                        </div>
                        <div>
                            <label for="follow_up_status_note" class="label">Status tindak lanjut</label>
                            <select id="follow_up_status_note" name="follow_up_status" class="input">
                                @foreach ([
                                    'belum_dihubungi' => 'Belum dihubungi',
                                    'dijadwalkan' => 'Dijadwalkan',
                                    'sudah_bimbingan' => 'Sudah bimbingan',
                                    'selesai' => 'Selesai',
                                ] as $k => $lbl)
                                    <option value="{{ $k }}" @selected(old('follow_up_status', 'sudah_bimbingan') === $k)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="topic" class="label">Topik pembahasan</label>
                        <input id="topic" name="topic" type="text" maxlength="160" placeholder="Contoh: Evaluasi kehadiran dan target UAS minimal" value="{{ old('topic') }}" class="input" required>
                    </div>
                    <div>
                        <label for="follow_up_action" class="label">Catatan &amp; tindak lanjut</label>
                        <textarea id="follow_up_action" name="follow_up_action" rows="3" class="input py-2" placeholder="Langkah yang disepakati bersama mahasiswa..." required>{{ old('follow_up_action') }}</textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="activity_assessment" class="label">Keaktifan mahasiswa</label>
                            <select id="activity_assessment" name="activity_assessment" class="input">
                                <option value="">Tidak diubah</option>
                                <option value="aktif" @selected($setting->dpa_activity_level === 'aktif')>Aktif</option>
                                <option value="cukup" @selected($setting->dpa_activity_level === 'cukup')>Cukup</option>
                                <option value="kurang" @selected($setting->dpa_activity_level === 'kurang')>Kurang</option>
                            </select>
                        </div>
                        <div>
                            <label for="thesis_status_note" class="label">Tugas akhir (Sem 7+)</label>
                            <select id="thesis_status_note" name="thesis_status" class="input">
                                <option value="">Belum relevan</option>
                                <option value="belum_mulai" @selected($setting->thesis_status === 'belum_mulai')>Belum mulai</option>
                                <option value="proposal" @selected($setting->thesis_status === 'proposal')>Proposal</option>
                                <option value="pengerjaan" @selected($setting->thesis_status === 'pengerjaan')>Pengerjaan skripsi/capstone</option>
                                <option value="sidang_terjadwal" @selected($setting->thesis_status === 'sidang_terjadwal')>Sidang terjadwal / selesai</option>
                            </select>
                        </div>
                    </div>
                    <label class="flex items-center gap-2.5 text-xs text-ink-2">
                        <input type="checkbox" name="confirmed_course_scores" value="1" class="size-4 accent-[var(--accent)]" @checked($setting->dpa_confirmed_tasks)>
                        <span>Konfirmasi nilai tugas/UTS semester berjalan sudah sesuai.</span>
                    </label>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan catatan</button>
                </form>

                @if ($notes->isNotEmpty())
                    <ul class="mt-6 space-y-3 border-t border-line pt-5">
                        @foreach ($notes as $note)
                            <li class="rounded-md border border-line bg-bg p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                    <span class="font-medium">{{ $note->topic }}</span>
                                    <span class="mono text-xs text-ink-3">{{ $note->guidance_date?->translatedFormat('j M Y') }} &middot; {{ $note->followUpLabel() }}</span>
                                </div>
                                <p class="mt-1.5 text-sm text-ink-2">{{ $note->follow_up_action }}</p>
                                @if ($note->activity_assessment)
                                    <p class="mt-1 text-xs text-ink-3">Keaktifan: {{ $note->activityLabel() }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <aside class="lg:col-span-4" style="display:flex; flex-direction:column; gap:1rem;">
            <section class="rounded-md border border-line bg-surface p-5" aria-labelledby="panel-monitoring">
                <h2 id="panel-monitoring" class="text-base font-medium">Pemantauan Cepat</h2>
                <form method="POST" action="{{ route('dpa.bimbingan.monitoring', $student) }}" class="mt-4 space-y-3.5">
                    @csrf
                    <div>
                        <label for="follow_up_status" class="label">Status tindak lanjut</label>
                        <select id="follow_up_status" name="follow_up_status" class="input">
                            @foreach ([
                                'belum_dihubungi' => 'Belum dihubungi',
                                'dijadwalkan' => 'Dijadwalkan',
                                'sudah_bimbingan' => 'Sudah bimbingan',
                                'selesai' => 'Selesai',
                            ] as $k => $lbl)
                                <option value="{{ $k }}" @selected($setting->follow_up_status === $k)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="dpa_activity_level" class="label">Keaktifan (bobot 10%)</label>
                        <select id="dpa_activity_level" name="dpa_activity_level" class="input">
                            <option value="">Belum dinilai</option>
                            <option value="aktif" @selected($setting->dpa_activity_level === 'aktif')>Aktif</option>
                            <option value="cukup" @selected($setting->dpa_activity_level === 'cukup')>Cukup</option>
                            <option value="kurang" @selected($setting->dpa_activity_level === 'kurang')>Kurang</option>
                        </select>
                    </div>
                    <div>
                        <label for="thesis_status" class="label">Tugas akhir (Sem 7+)</label>
                        <select id="thesis_status" name="thesis_status" class="input">
                            <option value="">Belum diisi</option>
                            <option value="belum_mulai" @selected($setting->thesis_status === 'belum_mulai')>Belum mulai</option>
                            <option value="proposal" @selected($setting->thesis_status === 'proposal')>Proposal</option>
                            <option value="pengerjaan" @selected($setting->thesis_status === 'pengerjaan')>Pengerjaan</option>
                            <option value="sidang_terjadwal" @selected($setting->thesis_status === 'sidang_terjadwal')>Sidang terjadwal / selesai</option>
                        </select>
                    </div>
                    <div>
                        <label for="last_guidance_at" class="label">Bimbingan terakhir</label>
                        <input id="last_guidance_at" name="last_guidance_at" type="date" value="{{ $setting->last_guidance_at?->toDateString() }}" class="input">
                    </div>
                    <label class="flex items-center gap-2 text-xs text-ink-2">
                        <input type="checkbox" name="dpa_confirmed_tasks" value="1" class="size-4 accent-[var(--accent)]" @checked($setting->dpa_confirmed_tasks)>
                        <span>Rekap nilai tugas sudah dikonfirmasi</span>
                    </label>
                    <button type="submit" class="btn btn-quiet btn-sm w-full">Perbarui status &amp; skor</button>
                </form>
            </section>

            <section class="rounded-md border border-line bg-surface p-5" aria-labelledby="dpa-per-sem">
                <h2 id="dpa-per-sem" class="text-base font-medium">Per semester</h2>
                <p class="mt-1 text-xs text-ink-3">Klik semester untuk melihat rinciannya.</p>
                <ul class="mt-3" style="display:flex; flex-direction:column; gap:4px;">
                    @foreach ($results->sortByDesc('semester') as $r)
                        @php $isActiveSem = $r->semester === $latest->semester; @endphp
                        <li>
                            <a href="{{ route('dpa.bimbingan.show', ['student' => $student, 'semester' => $r->semester]) }}"
                               class="flex items-center justify-between gap-3 text-ink"
                               style="padding:0.55rem 0.65rem; border-radius:8px; text-decoration:none; border:1px solid {{ $isActiveSem ? 'var(--accent)' : 'transparent' }}; background:{{ $isActiveSem ? 'var(--accent-soft)' : 'transparent' }};">
                                <span class="num text-sm {{ $isActiveSem ? 'font-semibold text-accent' : '' }}">Sem {{ $r->semester }}</span>
                                <span class="flex items-center gap-2.5">
                                    <span class="mono num text-xs text-ink-2">{{ \App\Support\Fmt::num($r->features['ips'] ?? 0) }}</span>
                                    <x-risk :level="$r->level" />
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-4 border-t border-line pt-3 text-xs text-ink-3">Akses aktif atas izin mahasiswa dan tercatat di log akses.</p>
            </section>
        </aside>
    </div>
@endif
@endsection