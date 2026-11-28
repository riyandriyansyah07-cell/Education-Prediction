@extends('layouts.app')

@section('title', 'Audit & Validasi AI')

@php
    $total = max(0, (int) $stats['total']);
    $validatedPct = $total > 0 ? round(($stats['validated'] / $total) * 100) : 0;
    $accPct = $stats['accuracy_pct'];
@endphp

@section('content')
<style>
    .val-hero {
        padding: 0.15rem 0 1.25rem;
        border-bottom: 1px solid var(--line);
    }
    .val-pill {
        display: inline-flex; align-items: center; gap: 0.45rem;
        padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 11px; font-weight: 600;
        border: 1px solid color-mix(in srgb, var(--accent) 30%, var(--line));
        background: var(--surface); color: var(--ink-2);
    }
    .val-steps {
        display: grid; gap: 0.75rem; grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 1.15rem; padding-top: 1.1rem; border-top: 1px solid color-mix(in srgb, var(--line) 80%, transparent);
    }
    .val-step {
        display: flex; align-items: flex-start; gap: 0.65rem;
        padding: 0.65rem 0.8rem; border-radius: 10px;
        background: color-mix(in srgb, var(--surface) 80%, var(--bg));
        border: 1px solid var(--line);
    }
    .val-step-num {
        width: 24px; height: 24px; border-radius: 9999px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 700; background: var(--accent); color: #ffffff;
    }
    .val-kpi-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
    .val-kpi {
        border-radius: 12px; border: 1px solid var(--line); background: var(--surface);
        padding: 1.2rem 1.3rem; display: flex; flex-direction: column; justify-content: space-between; min-width: 0;
    }
    .val-kpi-icon {
        width: 34px; height: 34px; border-radius: 9px;
        display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .val-mini-bar { height: 6px; width: 100%; border-radius: 9999px; background: var(--bg); overflow: hidden; margin-top: 0.6rem; border: 1px solid var(--line); }
    .val-mini-fill { height: 100%; border-radius: 9999px; }

    .val-card {
        border-radius: 12px; border: 1px solid var(--line); background: var(--surface);
        padding: 1.3rem 1.45rem; min-width: 0; max-width: 100%; overflow: hidden;
    }
    .val-compare-grid { display: grid; gap: 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: stretch; }
    .val-box {
        border-radius: 10px; border: 1px solid var(--line); background: var(--bg);
        padding: 1rem 1.15rem; display: flex; flex-direction: column; justify-content: space-between; min-width: 0;
    }
    .val-metric-grid {
        display: grid; gap: 0.5rem; grid-template-columns: repeat(5, minmax(0, 1fr));
        margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px solid var(--line);
    }
    .val-metric-cell {
        border-radius: 7px; background: var(--surface); border: 1px solid var(--line);
        padding: 0.4rem 0.5rem; text-align: center;
    }

    .val-filter-grid { display: grid; gap: 0.75rem; grid-template-columns: 1.4fr 1fr 1fr 0.75fr; align-items: end; }
    .val-action-grid { display: grid; gap: 0.75rem; grid-template-columns: auto minmax(150px, 0.9fr) minmax(200px, 1.5fr) auto; align-items: end; }
    .val-radio-group { display: inline-flex; gap: 0.35rem; padding: 0.25rem; border-radius: 9px; background: var(--bg); border: 1px solid var(--line); }
    .val-radio-opt { position: relative; cursor: pointer; margin: 0; }
    .val-radio-opt input { position: absolute; opacity: 0; width: 0; height: 0; }
    .val-radio-lbl {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.42rem 0.8rem; border-radius: 7px; font-size: 12px; font-weight: 600;
        color: var(--ink-2); white-space: nowrap; transition: all 0.15s ease;
    }
    .val-radio-opt input:checked + .val-radio-lbl.is-ok { background: var(--ok); color: #ffffff; }
    .val-radio-opt input:checked + .val-radio-lbl.is-warn { background: var(--warn); color: #ffffff; }

    .val-chip-row { display: flex; flex-wrap: wrap; gap: 0.45rem; }
    .val-chip {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.34rem 0.8rem; border-radius: 9999px; font-size: 12px; font-weight: 500;
        border: 1px solid var(--line); background: var(--bg); color: var(--ink-2); text-decoration: none;
    }
    .val-chip.is-active { background: var(--accent); border-color: var(--accent); color: #ffffff; }

    @media (max-width: 960px) {
        .val-steps { grid-template-columns: 1fr; }
        .val-compare-grid { grid-template-columns: 1fr; }
        .val-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .val-action-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .val-hero { padding: 0 0 1rem; }
        .val-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; }
        .val-kpi, .val-card { padding: 0.95rem; }
        .val-metric-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .val-filter-grid { grid-template-columns: 1fr; }
    }
</style>

{{-- HERO BANNER + ALUR VALIDASI 3 LANGKAH --}}
<section class="val-hero" aria-label="Ringkasan dan alur validasi AI">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div style="max-width: 44rem;">
            <span class="val-pill"><x-icon name="shield-check" size="size-3.5" /> Human-in-the-Loop &middot; Validator AI</span>
            <h1 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight">Audit &amp; Validasi Hasil AI Gemini</h1>
            <p class="mt-1.5 text-sm text-ink-2" style="line-height: 1.6;">
                Verifikasi kebenaran penilaian risiko AI Gemini dengan membandingkannya langsung terhadap rumus matematis standar FTII UHAMKA.
            </p>
        </div>
        <a href="{{ route('validator.audit.ekspor', array_filter($filters)) }}" class="btn btn-primary btn-sm">
            <x-icon name="download-simple" /> Unduh Laporan CSV
        </a>
    </div>

    <div class="val-steps" aria-label="Alur kerja validator">
        <div class="val-step">
            <span class="val-step-num">1</span>
            <div>
                <p class="text-xs font-semibold text-ink">Bandingkan Hasil</p>
                <p class="mt-0.5 text-xs text-ink-3">Cek skor AI Gemini vs Rumus Matematis &amp; 5 indikator akademik.</p>
            </div>
        </div>
        <div class="val-step">
            <span class="val-step-num">2</span>
            <div>
                <p class="text-xs font-semibold text-ink">Beri Keputusan</p>
                <p class="mt-0.5 text-xs text-ink-3">Pilih <strong>Sesuai</strong> atau <strong>Perlu Koreksi</strong> beserta catatan evaluasi.</p>
            </div>
        </div>
        <div class="val-step">
            <span class="val-step-num">3</span>
            <div>
                <p class="text-xs font-semibold text-ink">Pantau Akurasi</p>
                <p class="mt-0.5 text-xs text-ink-3">Persentase akurasi AI terhitung otomatis &amp; siap diekspor ke CSV.</p>
            </div>
        </div>
    </div>

    <details style="margin-top:0.9rem;border-radius:10px;border:1px solid var(--line);background:var(--surface);padding:0.7rem 0.95rem;">
        <summary style="cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:0.5rem;font-size:12.5px;font-weight:600;color:var(--ink);user-select:none;">
            <span>Panduan Koreksi Validasi AI</span>
            <span class="mono text-xs text-ink-3">Lihat panduan &#9662;</span>
        </summary>
        <div style="margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid var(--line);display:grid;gap:0.7rem;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));font-size:12px;line-height:1.5;color:var(--ink-2);">
            <div style="border-radius:8px;border:1px solid color-mix(in srgb, var(--ok) 35%, var(--line));background:var(--bg);padding:0.75rem 0.85rem;">
                <p style="font-weight:600;color:var(--ok);margin:0;">1. Pilih &ldquo;Sesuai&rdquo;</p>
                <p style="margin:0.3rem 0 0;">Hasil AI sama dengan perhitungan rumus (beda skor di bawah 1,5), dan alasannya cocok dengan nilai serta absensi mahasiswa.</p>
            </div>
            <div style="border-radius:8px;border:1px solid color-mix(in srgb, var(--warn) 40%, var(--line));background:var(--bg);padding:0.75rem 0.85rem;">
                <p style="font-weight:600;color:var(--warn);margin:0;">2. Pilih &ldquo;Perlu Koreksi&rdquo;</p>
                <p style="margin:0.3rem 0 0;">Hasil AI berbeda jauh dari rumus (beda skor 1,5 ke atas), keliru soal aturan absen di bawah 75%, atau menyebut masalah yang tidak ada di data.</p>
            </div>
            <div style="border-radius:8px;border:1px solid color-mix(in srgb, var(--accent) 35%, var(--line));background:var(--bg);padding:0.75rem 0.85rem;">
                <p style="font-weight:600;color:var(--accent);margin:0;">3. Centang &ldquo;Terapkan Langsung&rdquo;</p>
                <p style="margin:0.3rem 0 0;">Centang hanya jika label risiko di halaman mahasiswa &amp; DPA perlu langsung diganti ke level yang benar, lalu tulis alasan singkatnya.</p>
            </div>
        </div>
    </details>
</section>

{{-- 4 KARTU RINGKASAN VALIDASI --}}
<section class="val-kpi-grid mt-6" aria-label="Ringkasan metrik audit">
    <div class="val-kpi">
        <p class="text-xs font-medium text-ink-3">Total Dianalisis</p>
        <p class="mono mt-2 text-3xl font-semibold leading-none text-ink">{{ $stats['total'] }}</p>
        <p class="mt-2.5 text-xs text-ink-2"><strong>{{ $stats['ai_count'] }}</strong> Gemini AI &middot; {{ $stats['total'] - $stats['ai_count'] }} aturan</p>
    </div>

    <div class="val-kpi">
        <p class="text-xs font-medium text-ink-3">Akurasi AI (Sesuai)</p>
        <p class="mono mt-2 text-3xl font-semibold leading-none text-ink">
            {{ $accPct !== null ? $accPct.'%' : '—' }}
        </p>
        <p class="mt-2.5 text-xs text-ink-2">{{ $stats['sesuai'] }} sesuai &middot; {{ $stats['koreksi'] }} perlu koreksi</p>
    </div>

    <div class="val-kpi">
        <p class="text-xs font-medium text-ink-3">Selisih AI vs Rumus</p>
        <p class="mono mt-2 text-3xl font-semibold leading-none" style="color:{{ $stats['disagreement'] > 0 ? 'var(--bad)' : 'var(--ink)' }};">{{ $stats['disagreement'] }}</p>
        <p class="mt-2.5 text-xs text-ink-2">Prioritas audit (beda level atau &Delta; &ge; 1,5)</p>
    </div>

    <div class="val-kpi">
        <p class="text-xs font-medium text-ink-3">Progres Verifikasi</p>
        <p class="mono mt-2 text-3xl font-semibold leading-none text-ink">{{ $stats['validated'] }} <span class="text-sm font-normal text-ink-3">/ {{ $stats['total'] }}</span></p>
        <p class="mt-2.5 text-xs text-ink-2">{{ $stats['unvalidated'] }} menunggu ({{ $validatedPct }}% selesai)</p>
    </div>
</section>

{{-- FILTER & PENCARIAN --}}
<section class="val-card mt-6">
    <div class="val-chip-row">
        @php
            $statusTabs = [
                '' => 'Semua ('.$stats['total'].')',
                'selisih' => 'Prioritas Selisih ('.$stats['disagreement'].')',
                'belum' => 'Belum Divalidasi ('.$stats['unvalidated'].')',
                'sesuai' => 'Sesuai ('.$stats['sesuai'].')',
                'perlu_koreksi' => 'Perlu Koreksi ('.$stats['koreksi'].')',
            ];
        @endphp
        @foreach ($statusTabs as $key => $label)
            <a href="{{ route('validator.audit', array_filter(array_merge($filters, ['status' => $key]))) }}"
               class="val-chip {{ $filters['status'] === $key ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('validator.audit') }}" class="val-filter-grid mt-4">
        <input type="hidden" name="status" value="{{ $filters['status'] }}">
        <div>
            <label for="v-q" class="label text-xs">Cari nama / NIM</label>
            <input id="v-q" name="q" type="text" value="{{ $filters['q'] }}" placeholder="Ketik nama atau NIM..." class="input h-9 text-sm">
        </div>
        <div>
            <label for="v-sumber" class="label text-xs">Sumber Evaluasi</label>
            <select id="v-sumber" name="sumber" class="input h-9 text-xs">
                <option value="">Semua sumber</option>
                <option value="ai" @selected($filters['sumber'] === 'ai')>Khusus Gemini AI</option>
                <option value="aturan" @selected($filters['sumber'] === 'aturan')>Model Aturan / ML</option>
            </select>
        </div>
        <div>
            <label for="v-cakupan" class="label text-xs">Cakupan Semester</label>
            <select id="v-cakupan" name="cakupan" class="input h-9 text-xs">
                <option value="terbaru" @selected($filters['cakupan'] === 'terbaru')>Semester terbaru</option>
                <option value="semua" @selected($filters['cakupan'] === 'semua')>Semua semester</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm h-9 w-full">
            <x-icon name="magnifying-glass" /> Saring
        </button>
    </form>
</section>

@if ($errors->any())
    <div class="mt-4 rounded-md border border-bad/40 bg-bad/10 px-4 py-3 text-sm" style="color:var(--bad);" role="alert">
        {{ $errors->first() }}
    </div>
@endif

{{-- DAFTAR KARTU PERBANDINGAN AI VS RUMUS MATEMATIS --}}
<div class="mt-6 space-y-5">
    @forelse ($items as $item)
        @php
            $res = $item['result'];
            $stu = $item['student'];
            $val = $item['validation'];
            $aiPct = min(100, max(0, round(($item['ai_score'] / 10) * 100)));
            $rulePct = min(100, max(0, round(($item['rule_score'] / 10) * 100)));
            $aiColor = $item['ai_level'] === 'tinggi' ? 'var(--bad)' : ($item['ai_level'] === 'sedang' ? 'var(--warn)' : 'var(--ok)');
            $ruleColor = $item['rule_level'] === 'tinggi' ? 'var(--bad)' : ($item['rule_level'] === 'sedang' ? 'var(--warn)' : 'var(--ok)');
            $isKoreksi = ($val?->verdict === 'perlu_koreksi');
        @endphp
        <article class="val-card" style="{{ $item['is_disagreement'] ? 'border-left: 4px solid var(--bad);' : ($val?->verdict === 'sesuai' ? 'border-left: 4px solid var(--ok);' : 'border-left: 4px solid var(--line-strong);') }}">
            {{-- Header Kartu --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-semibold">{{ $stu?->name ?? 'Mahasiswa' }}</h2>
                        <span class="mono text-xs text-ink-3">{{ $stu?->username ?? substr((string) $res->student_ref, 0, 10) }}</span>
                        <span class="rounded border border-line bg-bg px-2 py-0.5 text-xs text-ink-2">Sem {{ $res->semester }}</span>
                        <span class="rounded px-2 py-0.5 text-xs font-medium" style="background: color-mix(in srgb, {{ $item['is_ai'] ? 'var(--accent)' : 'var(--ink-3)' }} 14%, transparent); color: {{ $item['is_ai'] ? 'var(--accent)' : 'var(--ink-2)' }};">
                            {{ $item['source_label'] }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-ink-3">
                        {{ $stu?->program_studi ?: 'Prodi belum ditetapkan' }} &middot; Diperbarui {{ $res->updated_at?->translatedFormat('j M Y, H:i') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($item['is_disagreement'])
                        <span class="rounded-full px-3 py-1 text-xs font-semibold" style="background: color-mix(in srgb, var(--bad) 13%, transparent); color: var(--bad);">
                            Selisih &Delta; {{ number_format($item['score_delta'], 2) }}
                        </span>
                    @else
                        <span class="rounded-full px-3 py-1 text-xs font-medium" style="background: color-mix(in srgb, var(--ok) 13%, transparent); color: var(--ok);">
                            Sepakat (&Delta; {{ number_format($item['score_delta'], 2) }})
                        </span>
                    @endif

                    @if ($val)
                        <span class="rounded-full px-3 py-1 text-xs font-semibold" style="background: color-mix(in srgb, {{ $val->verdict === 'sesuai' ? 'var(--ok)' : 'var(--warn)' }} 16%, transparent); color: {{ $val->verdict === 'sesuai' ? 'var(--ok)' : 'var(--warn)' }};">
                            {{ $val->verdictLabel() }}@if ($isKoreksi && $val->expected_level) &rarr; {{ strtoupper($val->expected_level) }}@endif
                        </span>
                    @else
                        <span class="rounded-full border border-line bg-bg px-3 py-1 text-xs text-ink-3">Belum divalidasi</span>
                    @endif
                </div>
            </div>

            {{-- Langkah 1: Perbandingan Sisi-ke-Sisi Proporsional --}}
            <div class="val-compare-grid mt-4">
                <div class="val-box">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold uppercase tracking-wider text-ink-3">1. Hasil {{ $item['source_label'] }}</p>
                            <x-risk :level="$item['ai_level']" />
                        </div>
                        <div class="mt-2.5 flex items-baseline justify-between gap-2">
                            <span class="mono text-2xl font-semibold">{{ number_format($item['ai_score'], 2) }} <span class="text-xs font-normal text-ink-3">/ 10</span></span>
                            <span class="text-xs text-ink-3">Skor evaluasi sistem</span>
                        </div>
                        <div class="val-mini-bar"><div class="val-mini-fill" style="width:{{ $aiPct }}%; background:{{ $aiColor }};"></div></div>
                    </div>

                    <div class="mt-3 pt-3 border-t border-line space-y-1.5 text-xs">
                        <p class="text-ink-2">
                            <strong class="text-ink">Faktor utama:</strong>
                            {{ $item['factors'] !== [] ? collect($item['factors'])->take(2)->pluck('label')->implode('; ') : 'Tidak ada faktor risiko signifikan.' }}
                        </p>
                        @if ($item['suggestions'] !== [])
                            <p class="text-ink-3" style="line-height:1.5;">
                                <strong class="text-ink-2">Saran:</strong> {{ is_string($item['suggestions'][0] ?? null) ? $item['suggestions'][0] : '-' }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="val-box">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold uppercase tracking-wider text-ink-3">2. Pembanding Matematis (RiskModel)</p>
                            <x-risk :level="$item['rule_level']" />
                        </div>
                        <div class="mt-2.5 flex items-baseline justify-between gap-2">
                            <span class="mono text-2xl font-semibold">{{ number_format($item['rule_score'], 2) }} <span class="text-xs font-normal text-ink-3">/ 10</span></span>
                            <span class="text-xs text-ink-3">Skor rumus 10 variabel</span>
                        </div>
                        <div class="val-mini-bar"><div class="val-mini-fill" style="width:{{ $rulePct }}%; background:{{ $ruleColor }};"></div></div>
                    </div>

                    <div class="val-metric-grid">
                        <div class="val-metric-cell">
                            <p style="font-size:10px;" class="text-ink-3">IPS</p>
                            <p class="mono mt-0.5 text-xs font-semibold">{{ $item['ips'] !== null ? number_format($item['ips'], 2) : '-' }}</p>
                        </div>
                        <div class="val-metric-cell">
                            <p style="font-size:10px;" class="text-ink-3">IPK</p>
                            <p class="mono mt-0.5 text-xs font-semibold">{{ $item['ipk'] !== null ? number_format($item['ipk'], 2) : '-' }}</p>
                        </div>
                        <div class="val-metric-cell">
                            <p style="font-size:10px;" class="text-ink-3">Hadir</p>
                            <p class="mono mt-0.5 text-xs font-semibold">{{ $item['attendance'] !== null ? number_format($item['attendance'], 0).'%' : '-' }}</p>
                        </div>
                        <div class="val-metric-cell">
                            <p style="font-size:10px;" class="text-ink-3">Gagal</p>
                            <p class="mono mt-0.5 text-xs font-semibold">{{ $item['failed_ratio'] !== null ? number_format($item['failed_ratio'] * 100, 0).'%' : '-' }}</p>
                        </div>
                        <div class="val-metric-cell">
                            <p style="font-size:10px;" class="text-ink-3">Tren IPS</p>
                            <p class="mono mt-0.5 text-xs font-semibold">{{ $item['ips_delta'] !== null ? ($item['ips_delta'] >= 0 ? '+' : '').number_format($item['ips_delta'], 2) : '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Langkah 2: Form Keputusan Validator --}}
            <form method="POST" action="{{ route('validator.audit.store', $res) }}" class="mt-4 border-t border-line pt-4">
                @csrf
                <div class="val-action-grid">
                    <div>
                        <span class="label text-xs">Keputusan Validasi</span>
                        <div class="val-radio-group" role="radiogroup" aria-label="Keputusan validasi">
                            <label class="val-radio-opt">
                                <input type="radio" name="verdict" value="sesuai" @checked(! $isKoreksi) onchange="toggleExpectedLevel('sesuai', {{ $res->id }})">
                                <span class="val-radio-lbl is-ok">&#10003; Sesuai</span>
                            </label>
                            <label class="val-radio-opt">
                                <input type="radio" name="verdict" value="perlu_koreksi" @checked($isKoreksi) onchange="toggleExpectedLevel('perlu_koreksi', {{ $res->id }})">
                                <span class="val-radio-lbl is-warn">! Perlu Koreksi</span>
                            </label>
                        </div>
                    </div>

                    <div id="expected-wrap-{{ $res->id }}" style="{{ $isKoreksi ? '' : 'display:none;' }}">
                        <label class="label text-xs">Level Seharusnya</label>
                        <select name="expected_level" class="input h-9 text-xs">
                            <option value="rendah" @selected(($val?->expected_level ?? $item['rule_level']) === 'rendah')>Rendah</option>
                            <option value="sedang" @selected(($val?->expected_level ?? $item['rule_level']) === 'sedang')>Sedang</option>
                            <option value="tinggi" @selected(($val?->expected_level ?? $item['rule_level']) === 'tinggi')>Tinggi</option>
                        </select>
                    </div>

                    <div>
                        <label class="label text-xs">Catatan Evaluasi (Opsional)</label>
                        <input type="text" name="notes" value="{{ $val?->notes }}" maxlength="500" placeholder="Tuliskan alasan atau catatan evaluasi AI..." class="input h-9 text-xs">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm h-9" style="white-space:nowrap;">Simpan Validasi</button>
                </div>

                <div id="override-wrap-{{ $res->id }}" style="margin-top:0.85rem;{{ $isKoreksi ? '' : 'display:none;' }}">
                    <label class="inline-flex items-center gap-2 text-xs text-ink-2" style="cursor:pointer;">
                        <input type="checkbox" name="apply_override" value="1" @checked($val?->override_applied) class="size-4 accent-[var(--accent)]">
                        <span>Terapkan langsung koreksi level risiko ini ke halaman mahasiswa (opsional)</span>
                    </label>
                </div>

                @if ($val)
                    <p class="text-xs text-ink-3" style="margin-top:0.75rem;">
                        Terakhir divalidasi oleh <strong>{{ $val->validator?->name ?? 'Validator' }}</strong> pada {{ $val->validated_at?->translatedFormat('j M Y, H:i') }}{{ $val->override_applied ? ' · Koreksi level telah diterapkan' : '' }}.
                    </p>
                @endif
            </form>
        </article>
    @empty
        <div class="val-card text-center text-sm text-ink-2" style="padding: 2.5rem;">
            Belum ada hasil analisis risiko yang cocok dengan filter ini.
        </div>
    @endforelse
</div>

<script>
function toggleExpectedLevel(val, id) {
    const isKoreksi = val === 'perlu_koreksi';
    const w1 = document.getElementById('expected-wrap-' + id);
    const w2 = document.getElementById('override-wrap-' + id);
    if (w1) w1.style.display = isKoreksi ? '' : 'none';
    if (w2) w2.style.display = isKoreksi ? '' : 'none';
}
</script>
@endsection
