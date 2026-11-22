@extends('layouts.app')

@section('title', $student->name)

@php
    $setting = $setting ?? new \App\Models\StudentSetting(['follow_up_status' => 'belum_dihubungi']);
    $courses = $courses ?? collect();
    $notes = $notes ?? collect();
    $snapshots = $snapshots ?? collect();
    $enrichmentRecords = $enrichmentRecords ?? collect();
    $enrichment = $enrichment ?? \App\Support\EnrichmentCatalog::evaluateProgress($enrichmentRecords, (int) ($latest?->semester ?? 1));
@endphp

@section('content')
<a href="{{ route('dpa.bimbingan') }}" class="no-print inline-flex items-center gap-1 text-sm font-medium text-accent hover:underline"><x-icon name="arrow-right" class="rotate-180" size="size-4" /> Daftar bimbingan</a>
<div class="mt-4 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="rise text-3xl sm:text-4xl">{{ $student->name }}</h1>
        <p class="rise mono mt-1 text-ink-2" style="--i: 1">NIM: {{ $student->username }} &middot; Status: {{ $setting->followUpLabel() }}</p>
    </div>
</div>

@if (! $latest)
    <p class="mt-10 max-w-xl text-ink-2">Mahasiswa ini belum mengimpor data, jadi belum ada hasil risiko.</p>
@else
    @php $version = $latest->modelVersion; $thresholds = $version?->params ?? \App\Support\RiskModel::DEFAULT_THRESHOLDS; @endphp
    <div class="mt-10 grid gap-12 lg:grid-cols-12">
        <div class="space-y-12 lg:col-span-8">
            <x-risk-panel :result="$latest" :thresholds="$thresholds" :version="$version?->version" audience="dpa" />

            @if ($courses->isNotEmpty())
                <section class="border-t border-line pt-8" aria-labelledby="proyeksi-mk-dpa">
                    <h2 id="proyeksi-mk-dpa" class="text-xl">Proyeksi Nilai &amp; Target UAS Minimal (Semester {{ $latest->semester }})</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="tbl min-w-[42rem]">
                            <thead>
                                <tr><th>Mata Kuliah</th><th>SKS</th><th>Hadir</th><th>UTS / Tugas / UAS</th><th>Proyeksi N</th><th>U_min (C / B)</th><th>Sumber</th><th>Risiko</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($courses as $c)
                                    <tr>
                                        <td><span class="font-medium">{{ $c->course_name }}</span><span class="mono block text-xs text-ink-3">{{ $c->course_code }}</span></td>
                                        <td class="num">{{ $c->credits }}</td>
                                        <td class="num">{{ \App\Support\Fmt::pct($c->attendance) }}</td>
                                        <td class="mono text-xs">{{ $c->uts_score ?? '-' }} / {{ $c->assignment_score ?? '-' }} / {{ $c->uas_score ?? ($c->grade_input ?? '-') }}</td>
                                        <td class="num mono font-medium">{{ $c->projected_score !== null ? \App\Support\Fmt::num($c->projected_score, 1) : '-' }}</td>
                                        <td class="mono text-xs">
                                            @if ($c->min_uas_for_c !== null)
                                                C: {{ \App\Support\Fmt::num($c->min_uas_for_c, 0) }} | B: {{ \App\Support\Fmt::num($c->min_uas_for_b ?? 0, 0) }}
                                            @else
                                                KHS Resmi
                                            @endif
                                        </td>
                                        <td class="text-xs text-ink-2">{{ $c->trustLabel() }}</td>
                                        <td><x-risk :level="$c->course_risk_level ?? 'rendah'" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            <section class="risk-{{ $enrichment['risk_level'] }} border-t border-line pt-8" aria-labelledby="pengayaan-dpa">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="pengayaan-dpa" class="text-xl">Progres SKS Pengayaan (Target 20 SKS)</h2>
                    <span class="mono text-sm font-medium">{{ $enrichment['converted_sks'] }} / {{ $enrichment['target_sks'] }} SKS terkonversi</span>
                </div>
                <p class="mt-2 text-sm text-ink-2">{{ $enrichment['status_message'] }}</p>

                <div class="mt-4 grid gap-3 sm:grid-cols-3 text-xs">
                    <div class="rounded-lg border border-line bg-surface p-3">
                        <p class="text-ink-3">Terkonversi KRS</p>
                        <p class="mono mt-1 text-lg font-medium">{{ $enrichment['converted_sks'] }} SKS</p>
                    </div>
                    <div class="rounded-lg border border-line bg-surface p-3">
                        <p class="text-ink-3">Bukti Siap Rekognisi</p>
                        <p class="mono mt-1 text-lg font-medium">{{ $enrichment['ready_sks'] }} SKS</p>
                    </div>
                    <div class="rounded-lg border border-line bg-surface p-3">
                        <p class="text-ink-3">Sedang Dicicil</p>
                        <p class="mono mt-1 text-lg font-medium">{{ $enrichment['in_progress_sks'] }} SKS</p>
                    </div>
                </div>

                @if ($enrichmentRecords->isNotEmpty())
                    <ul class="mt-4 divide-y divide-line rounded-lg border border-line bg-surface px-4 text-xs">
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

            <section class="border-t border-line pt-8" aria-labelledby="catatan-bimbingan">
                <h2 id="catatan-bimbingan" class="text-2xl">Catatan Bimbingan &amp; Tindak Lanjut</h2>
                <form method="POST" action="{{ route('dpa.bimbingan.catatan', $student) }}" class="mt-4 space-y-4 rounded-md border border-line bg-surface p-5">
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
                        <label for="follow_up_action" class="label">Catatan &amp; rencana tindak lanjut</label>
                        <textarea id="follow_up_action" name="follow_up_action" rows="3" class="input py-2" placeholder="Langkah yang disepakati bersama mahasiswa..." required>{{ old('follow_up_action') }}</textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="activity_assessment" class="label">Penilaian keaktifan (Input DPA)</label>
                            <select id="activity_assessment" name="activity_assessment" class="input">
                                <option value="">Tidak diubah</option>
                                <option value="aktif" @selected($setting->dpa_activity_level === 'aktif')>Aktif (Aman - 0 poin)</option>
                                <option value="cukup" @selected($setting->dpa_activity_level === 'cukup')>Cukup (Waspada - 0,5 poin)</option>
                                <option value="kurang" @selected($setting->dpa_activity_level === 'kurang')>Kurang (Kritis - 1,0 poin)</option>
                            </select>
                        </div>
                        <div>
                            <label for="thesis_status_note" class="label">Status tugas akhir (Sem 7+)</label>
                            <select id="thesis_status_note" name="thesis_status" class="input">
                                <option value="">Belum relevan / tidak diubah</option>
                                <option value="belum_mulai" @selected($setting->thesis_status === 'belum_mulai')>Belum mulai</option>
                                <option value="proposal" @selected($setting->thesis_status === 'proposal')>Penyusunan proposal</option>
                                <option value="pengerjaan" @selected($setting->thesis_status === 'pengerjaan')>Pengerjaan skripsi/capstone</option>
                                <option value="sidang_terjadwal" @selected($setting->thesis_status === 'sidang_terjadwal')>Sidang terjadwal / selesai</option>
                            </select>
                        </div>
                    </div>
                    <label class="flex items-center gap-2.5 text-sm">
                        <input type="checkbox" name="confirmed_course_scores" value="1" class="size-4 accent-[var(--accent)]" @checked($setting->dpa_confirmed_tasks)>
                        <span>Konfirmasi kebenaran nilai tugas/UTS semester berjalan (menaikkan tingkat kepercayaan data ke Tingkat 2: Terkonfirmasi DPA)</span>
                    </label>
                    <button type="submit" class="btn btn-primary">Simpan catatan bimbingan</button>
                </form>

                @if ($notes->isNotEmpty())
                    <ul class="mt-6 space-y-4">
                        @foreach ($notes as $note)
                            <li class="rounded-md border border-line p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                    <span class="font-medium">{{ $note->topic }}</span>
                                    <span class="mono text-xs text-ink-3">{{ $note->guidance_date?->translatedFormat('j F Y') }} &middot; {{ $note->followUpLabel() }}</span>
                                </div>
                                <p class="mt-2 text-sm text-ink-2">{{ $note->follow_up_action }}</p>
                                @if ($note->activity_assessment)
                                    <p class="mt-1.5 text-xs text-ink-3">Penilaian keaktifan: {{ $note->activityLabel() }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <aside class="space-y-8 lg:col-span-4">
            <section class="rounded-md border border-line bg-surface p-5" aria-labelledby="panel-monitoring">
                <h2 id="panel-monitoring" class="text-lg font-medium">Pemantauan Cepat DPA</h2>
                <form method="POST" action="{{ route('dpa.bimbingan.monitoring', $student) }}" class="mt-4 space-y-4">
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
                        <label for="dpa_activity_level" class="label">Keaktifan mahasiswa (bobot 10%)</label>
                        <select id="dpa_activity_level" name="dpa_activity_level" class="input">
                            <option value="">Belum dinilai (dilewati)</option>
                            <option value="aktif" @selected($setting->dpa_activity_level === 'aktif')>Aktif</option>
                            <option value="cukup" @selected($setting->dpa_activity_level === 'cukup')>Cukup</option>
                            <option value="kurang" @selected($setting->dpa_activity_level === 'kurang')>Kurang</option>
                        </select>
                    </div>
                    <div>
                        <label for="thesis_status" class="label">Status tugas akhir (Sem 7+)</label>
                        <select id="thesis_status" name="thesis_status" class="input">
                            <option value="">Belum diisi</option>
                            <option value="belum_mulai" @selected($setting->thesis_status === 'belum_mulai')>Belum mulai</option>
                            <option value="proposal" @selected($setting->thesis_status === 'proposal')>Proposal</option>
                            <option value="pengerjaan" @selected($setting->thesis_status === 'pengerjaan')>Pengerjaan</option>
                            <option value="sidang_terjadwal" @selected($setting->thesis_status === 'sidang_terjadwal')>Sidang terjadwal / selesai</option>
                        </select>
                    </div>
                    <div>
                        <label for="last_guidance_at" class="label">Tanggal bimbingan terakhir</label>
                        <input id="last_guidance_at" name="last_guidance_at" type="date" value="{{ $setting->last_guidance_at?->toDateString() }}" class="input">
                    </div>
                    <label class="flex items-center gap-2 text-xs text-ink-2">
                        <input type="checkbox" name="dpa_confirmed_tasks" value="1" class="size-4 accent-[var(--accent)]" @checked($setting->dpa_confirmed_tasks)>
                        <span>Tandai rekap nilai tugas telah dikonfirmasi DPA</span>
                    </label>
                    <button type="submit" class="btn btn-quiet btn-sm w-full">Perbarui status &amp; skor</button>
                </form>
            </section>

            <section>
                <h2 class="text-xl">Per semester</h2>
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
                <p class="mt-6 text-sm text-ink-3">Kamu melihat halaman ini karena mahasiswa memberi izin. Setiap perubahan penilaian atau catatan tercatat di log akses.</p>
            </section>
        </aside>
    </div>
@endif
@endsection