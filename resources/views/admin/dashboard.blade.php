@extends('layouts.app')

@section('title', 'Dasbor ringkasan')

@php
    $totalMhs = max(0, (int) $totals['students']);
    $analyzedPct = $totalMhs > 0 ? round(($totals['students_analyzed'] / $totalMhs) * 100) : 0;
    $consentPct = $totalMhs > 0 ? round(($totals['students_consented'] / $totalMhs) * 100) : 0;
    $attentionCount = $riskCounts['tinggi'] + $riskCounts['sedang'];
    $attentionPct = $totalMhs > 0 ? round(($attentionCount / $totalMhs) * 100, 1) : 0.0;

    $donutRadius = 56;
    $donutCircumference = 2 * M_PI * $donutRadius;
    $donutSegments = [
        ['key' => 'tinggi', 'label' => 'Risiko Tinggi', 'short' => 'Tinggi', 'count' => $riskCounts['tinggi'], 'pct' => $riskPcts['tinggi'], 'color' => 'var(--bad)'],
        ['key' => 'sedang', 'label' => 'Risiko Sedang', 'short' => 'Sedang', 'count' => $riskCounts['sedang'], 'pct' => $riskPcts['sedang'], 'color' => 'var(--warn)'],
        ['key' => 'rendah', 'label' => 'Risiko Rendah', 'short' => 'Rendah', 'count' => $riskCounts['rendah'], 'pct' => $riskPcts['rendah'], 'color' => 'var(--ok)'],
        ['key' => 'belum', 'label' => 'Belum Dihitung', 'short' => 'Belum', 'count' => $riskCounts['belum'], 'pct' => $riskPcts['belum'], 'color' => 'var(--line-strong)'],
    ];
    $maxProdiTotal = max(1, (int) ($prodiStats->max('total') ?? 1));
    $maxAngkatanTotal = max(1, (int) ($angkatanStats->max('total') ?? 1));
    $maxDpaTotal = max(1, (int) ($dpaStats->max('total') ?? 1));
@endphp

@section('content')
<style>
    .adm-hero {
        position: relative;
        overflow: hidden;
        border-radius: 14px;
        border: 1px solid var(--line);
        background: linear-gradient(135deg, color-mix(in srgb, var(--accent) 11%, var(--surface)) 0%, var(--surface) 58%, color-mix(in srgb, var(--ok) 6%, var(--surface)) 100%);
        padding: 1.5rem 1.75rem;
    }
    .adm-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 600;
        border: 1px solid color-mix(in srgb, var(--accent) 30%, var(--line));
        background: var(--surface);
        color: var(--ink-2);
    }
    .adm-dot { width: 7px; height: 7px; border-radius: 9999px; background: var(--ok); display: inline-block; }
    .adm-kpi-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
    .adm-kpi {
        position: relative;
        border-radius: 12px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: 1.2rem 1.3rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-width: 0;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }
    .adm-kpi:hover { transform: translateY(-2px); box-shadow: 0 8px 24px -12px rgba(0,0,0,0.16); border-color: var(--line-strong); }
    .adm-kpi-icon {
        width: 36px; height: 36px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .adm-mini-bar { height: 5px; width: 100%; border-radius: 9999px; background: var(--bg); overflow: hidden; margin-top: 0.65rem; }
    .adm-mini-fill { height: 100%; border-radius: 9999px; transition: width 0.4s ease; }

    .adm-analytics-grid { display: grid; gap: 1.25rem; grid-template-columns: minmax(290px, 1fr) minmax(340px, 1.45fr); }
    .adm-panel {
        border-radius: 12px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: 1.35rem 1.45rem;
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }
    .adm-donut-wrap { display: flex; align-items: center; gap: 1.35rem; margin-top: 1.25rem; }
    .adm-donut-seg { cursor: pointer; transition: stroke-width 0.18s ease, opacity 0.18s ease; }
    .adm-donut-seg:hover { stroke-width: 21; }
    .adm-legend-btn {
        width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;
        padding: 0.45rem 0.6rem; border-radius: 8px; border: 1px solid transparent;
        background: transparent; cursor: pointer; text-align: left; font-size: 12px;
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .adm-legend-btn:hover, .adm-legend-btn.is-active { background: var(--bg); border-color: var(--line); }

    .adm-tabs { display: inline-flex; gap: 0.25rem; padding: 0.25rem; border-radius: 9px; background: var(--bg); border: 1px solid var(--line); }
    .adm-tab {
        padding: 0.35rem 0.75rem; border-radius: 7px; font-size: 12px; font-weight: 500;
        color: var(--ink-2); background: transparent; border: none; cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    }
    .adm-tab.is-active { background: var(--surface); color: var(--ink); box-shadow: 0 1px 3px rgba(0,0,0,0.08); }

    .adm-bar-row {
        padding: 0.55rem 0.65rem; border-radius: 9px; cursor: pointer;
        transition: background 0.15s ease;
    }
    .adm-bar-row:hover { background: var(--bg); }
    .adm-bar-track { height: 11px; width: 100%; border-radius: 9999px; overflow: hidden; background: var(--bg); border: 1px solid var(--line); }
    .adm-bar-fill { display: flex; height: 100%; border-radius: 9999px; overflow: hidden; }

    .adm-chip-row { display: flex; flex-wrap: wrap; gap: 0.45rem; }
    .adm-chip {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.32rem 0.75rem; border-radius: 9999px; font-size: 12px; font-weight: 500;
        border: 1px solid var(--line); background: var(--bg); color: var(--ink-2);
        cursor: pointer; transition: all 0.15s ease;
    }
    .adm-chip:hover { border-color: var(--line-strong); color: var(--ink); }
    .adm-chip.is-active { background: var(--accent); border-color: var(--accent); color: #ffffff; }

    .adm-filter-grid { display: grid; gap: 0.75rem; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); align-items: end; }
    .adm-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; max-width: 100%; width: 100%; }
    .adm-table th, .adm-table td { padding: 0.72rem 0.75rem; vertical-align: middle; }
    .adm-table th.num, .adm-table td.num { white-space: nowrap; text-align: right; }
    .adm-detail-row { display: none; background: color-mix(in srgb, var(--accent) 4%, var(--bg)); }
    .adm-detail-row.is-open { display: table-row; }
    .adm-mobile-hint { display: none; }

    @media (max-width: 960px) {
        .adm-analytics-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .adm-hero { padding: 1.15rem; }
        .adm-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; }
        .adm-kpi { padding: 0.95rem; }
        .adm-panel { padding: 1rem; }
        .adm-donut-wrap { flex-wrap: wrap; justify-content: center; gap: 1rem; }
        .adm-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.65rem; }
        .adm-table th, .adm-table td { padding: 0.55rem 0.6rem; }
        .adm-mobile-hint { display: block; margin-top: 0.35rem; font-size: 11px; color: var(--ink-3); }
    }
    @media (max-width: 360px) {
        .adm-kpi-grid, .adm-filter-grid { grid-template-columns: 1fr; }
    }
</style>

{{-- HERO EXECUTIVE BANNER --}}
<section class="adm-hero" aria-label="Ringkasan eksekutif">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div style="max-width: 44rem;">
            <div class="flex flex-wrap items-center gap-2">
                <span class="adm-pill"><span class="adm-dot"></span> Monitoring Aktif &middot; FTII UHAMKA</span>
                <span class="adm-pill">Model: {{ $activeModel ? $activeModel->version : 'Aturan bawaan' }}</span>
                @php $aiH = $aiHealth ?? ['status' => 'ready', 'model' => 'gemini-3.1-flash-lite', 'consecutive_failures' => 0, 'last_error' => null]; @endphp
                @if (($aiH['status'] ?? 'ready') === 'degraded')
                    <span class="adm-pill" title="{{ $aiH['last_error'] ?? 'Panggilan AI terakhir gagal' }}" style="border-color:color-mix(in srgb, var(--bad) 45%, var(--line));color:var(--bad);">
                        <span class="adm-dot" style="background:var(--bad);"></span>
                        AI: Gagal {{ $aiH['consecutive_failures'] }}x (cadangan RiskModel)
                    </span>
                @elseif (($aiH['status'] ?? 'ready') === 'healthy')
                    <span class="adm-pill" style="border-color:color-mix(in srgb, var(--ok) 40%, var(--line));">
                        <span class="adm-dot" style="background:var(--ok);"></span>
                        AI: {{ $aiH['model'] }} &middot; Sehat
                    </span>
                @else
                    <span class="adm-pill">
                        <span class="adm-dot" style="background:var(--accent);"></span>
                        AI: {{ $aiH['model'] }} &middot; Siap
                    </span>
                @endif
            </div>
            <h1 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight">Dasbor Ringkasan Akademik</h1>
            <p class="mt-2 text-sm text-ink-2" style="line-height: 1.6;">
                Dari <strong>{{ $totals['students'] }} mahasiswa</strong> terdaftar bersama <strong>{{ $totals['dpas'] }} DPA</strong>,
                terdapat <strong style="color:var(--bad);">{{ $riskCounts['tinggi'] }} risiko Tinggi</strong> dan
                <strong style="color:var(--warn);">{{ $riskCounts['sedang'] }} risiko Sedang</strong>
                ({{ $attentionPct }}% total perlu perhatian). Klik grafik atau baris kohort di bawah untuk langsung menyaring data.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a id="btn-export-csv" href="{{ route('admin.dasbor.ekspor', array_filter($filters)) }}" class="btn btn-quiet btn-sm">
                <x-icon name="download-simple" /> Unduh Rekap CSV
            </a>
            <a href="{{ route('admin.akun.baru') }}" class="btn btn-primary btn-sm">
                <x-icon name="plus" /> Tambah Akun
            </a>
        </div>
    </div>
</section>

{{-- BLOK 1: 4 KARTU KPI INTERAKTIF --}}
<section class="adm-kpi-grid mt-6" aria-label="Indikator utama">
    <div class="adm-kpi" style="border-top: 3px solid var(--accent);">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-xs font-medium text-ink-3">Total Mahasiswa</p>
                <p class="mono mt-2 text-3xl font-semibold leading-none">{{ $totals['students'] }}</p>
            </div>
            <span class="adm-kpi-icon" style="background: color-mix(in srgb, var(--accent) 14%, transparent); color: var(--accent);">
                <x-icon name="graduation-cap" />
            </span>
        </div>
        <div>
            <div class="adm-mini-bar"><div class="adm-mini-fill" style="width:{{ $analyzedPct }}%; background:var(--accent);"></div></div>
            <p class="mt-2 text-xs text-ink-2">{{ $totals['students_active'] }} aktif &middot; <strong>{{ $analyzedPct }}%</strong> sudah dianalisis</p>
        </div>
    </div>

    <div class="adm-kpi" style="border-top: 3px solid var(--ok);">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-xs font-medium text-ink-3">Total DPA &amp; Admin</p>
                <p class="mono mt-2 text-3xl font-semibold leading-none">{{ $totals['dpas'] }} <span class="text-sm font-normal text-ink-3">DPA</span></p>
            </div>
            <span class="adm-kpi-icon" style="background: color-mix(in srgb, var(--ok) 14%, transparent); color: var(--ok);">
                <x-icon name="users" />
            </span>
        </div>
        <div>
            <div class="adm-mini-bar"><div class="adm-mini-fill" style="width:{{ $totals['students'] > 0 ? round((($totals['students'] - $totals['students_without_dpa']) / $totals['students']) * 100) : 0 }}%; background:var(--ok);"></div></div>
            <p class="mt-2 text-xs text-ink-2">
                {{ $totals['dpas_active'] }} DPA aktif &middot; {{ $totals['admins'] }} admin
                @if ($totals['students_without_dpa'] > 0)
                    &middot; <span style="color:var(--warn);">{{ $totals['students_without_dpa'] }} tanpa DPA</span>
                @endif
            </p>
        </div>
    </div>

    <div class="adm-kpi" style="border-top: 3px solid var(--bad); cursor:pointer;" onclick="dashFilterByLevel('tinggi')">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-xs font-medium text-ink-3">Perlu Perhatian</p>
                <p class="mono mt-2 text-3xl font-semibold leading-none" style="color:var(--bad);">
                    {{ $attentionCount }} <span class="text-sm font-normal text-ink-3">({{ $riskCounts['tinggi'] }} tinggi)</span>
                </p>
            </div>
            <span class="adm-kpi-icon" style="background: color-mix(in srgb, var(--bad) 14%, transparent); color: var(--bad);">
                <x-icon name="warning" />
            </span>
        </div>
        <div>
            <div class="adm-mini-bar"><div class="adm-mini-fill" style="width:{{ $attentionPct }}%; background:var(--bad);"></div></div>
            <p class="mt-2 text-xs text-ink-2">{{ $riskPcts['tinggi'] }}% tinggi &middot; {{ $riskPcts['sedang'] }}% sedang</p>
        </div>
    </div>

    <div class="adm-kpi" style="border-top: 3px solid var(--warn);">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-xs font-medium text-ink-3">Izin DPA &amp; Tiket</p>
                <p class="mono mt-2 text-3xl font-semibold leading-none">{{ $totals['students_consented'] }} <span class="text-sm font-normal text-ink-3">izin</span></p>
            </div>
            <span class="adm-kpi-icon" style="background: color-mix(in srgb, var(--warn) 14%, transparent); color: var(--warn);">
                <x-icon name="shield-check" />
            </span>
        </div>
        <div>
            <div class="adm-mini-bar"><div class="adm-mini-fill" style="width:{{ $consentPct }}%; background:var(--warn);"></div></div>
            <p class="mt-2 text-xs text-ink-2">
                {{ $consentPct }}% memberi izin &middot;
                <a href="{{ route('admin.tiket') }}" class="text-accent font-medium hover:underline">{{ $totals['pending_tickets'] }} tiket baru</a>
            </p>
        </div>
    </div>
</section>

{{-- BLOK 2: ANALITIK INTERAKTIF (DONAT + GRAFIK TAB PRODI / ANGKATAN / DPA) --}}
<section class="adm-analytics-grid mt-6" aria-label="Visualisasi analitik risiko">
    {{-- Kiri: Interactive Donut Chart --}}
    <div class="adm-panel">
        <div class="flex items-baseline justify-between gap-2">
            <div>
                <h2 class="text-base font-semibold">Proporsi Risiko Keseluruhan</h2>
                <p class="mt-0.5 text-xs text-ink-3">Arahkan kursor atau klik segmen untuk menyaring daftar.</p>
            </div>
            <span class="mono text-xs text-ink-3">n = {{ $totalMhs }}</span>
        </div>

        <div class="adm-donut-wrap">
            <svg viewBox="0 0 148 148" width="144" height="144" role="img" aria-label="Diagram donat distribusi risiko" style="flex-shrink:0;">
                <circle cx="74" cy="74" r="{{ $donutRadius }}" fill="none" stroke="var(--line)" stroke-width="17" />
                @php $offset = 0.0; @endphp
                @if ($totalMhs > 0)
                    @foreach ($donutSegments as $seg)
                        @if ($seg['count'] > 0)
                            @php $segLen = ($seg['count'] / $totalMhs) * $donutCircumference; @endphp
                            <circle
                                class="adm-donut-seg"
                                cx="74" cy="74" r="{{ $donutRadius }}"
                                fill="none"
                                stroke="{{ $seg['color'] }}"
                                stroke-width="17"
                                stroke-dasharray="{{ number_format($segLen, 2, '.', '') }} {{ number_format($donutCircumference, 2, '.', '') }}"
                                stroke-dashoffset="{{ number_format(-$offset, 2, '.', '') }}"
                                transform="rotate(-90 74 74)"
                                onmouseenter="dashHoverDonut('{{ $seg['count'] }}', '{{ $seg['short'] }} ({{ $seg['pct'] }}%)')"
                                onmouseleave="dashHoverDonut('{{ $totalMhs }}', 'Mahasiswa')"
                                onclick="dashFilterByLevel('{{ $seg['key'] }}')"
                            >
                                <title>{{ $seg['label'] }}: {{ $seg['count'] }} mahasiswa ({{ $seg['pct'] }}%)</title>
                            </circle>
                            @php $offset += $segLen; @endphp
                        @endif
                    @endforeach
                @endif
                <text id="donut-center-val" x="74" y="70" text-anchor="middle" fill="var(--ink)" font-size="25" font-weight="700" font-family="monospace">{{ $totalMhs }}</text>
                <text id="donut-center-lbl" x="74" y="88" text-anchor="middle" fill="var(--ink-3)" font-size="10.5">Mahasiswa</text>
            </svg>

            <div class="flex-1 space-y-1.5" style="min-width: 140px;">
                @foreach ($donutSegments as $seg)
                    <button type="button" class="adm-legend-btn" data-legend-level="{{ $seg['key'] }}"
                        onmouseenter="dashHoverDonut('{{ $seg['count'] }}', '{{ $seg['short'] }} ({{ $seg['pct'] }}%)')"
                        onmouseleave="dashHoverDonut('{{ $totalMhs }}', 'Mahasiswa')"
                        onclick="dashFilterByLevel('{{ $seg['key'] }}')">
                        <span style="display:inline-flex; align-items:center; gap:0.45rem; white-space:nowrap;">
                            <span style="width:10px; height:10px; border-radius:3px; background:{{ $seg['color'] }}; display:inline-block;"></span>
                            {{ $seg['short'] }}
                        </span>
                        <span class="mono font-semibold" style="white-space:nowrap;">
                            {{ $seg['count'] }} <span class="font-normal text-ink-3">({{ $seg['pct'] }}%)</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Kanan: Interactive Tabbed Comparison Chart (Prodi / Angkatan / DPA) --}}
    <div class="adm-panel">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold">Grafik Perbandingan Risiko</h2>
                <p class="mt-0.5 text-xs text-ink-3">Klik batang grafik untuk langsung memfilter mahasiswa.</p>
            </div>
            <div class="adm-tabs" role="tablist" aria-label="Pilih dimensi grafik">
                <button type="button" class="adm-tab is-active" data-chart-tab="prodi" onclick="dashSwitchTab('prodi')">Program Studi</button>
                <button type="button" class="adm-tab" data-chart-tab="angkatan" onclick="dashSwitchTab('angkatan')">Angkatan</button>
                <button type="button" class="adm-tab" data-chart-tab="dpa" onclick="dashSwitchTab('dpa')">Beban DPA</button>
            </div>
        </div>

        {{-- Tab 1: Program Studi --}}
        <div data-chart-panel="prodi" class="mt-4 space-y-2">
            @forelse ($prodiStats as $p)
                @php
                    $barW = round(($p['total'] / $maxProdiTotal) * 100, 1);
                    $pT = $p['total'] > 0 ? round(($p['tinggi'] / $p['total']) * 100, 1) : 0;
                    $pS = $p['total'] > 0 ? round(($p['sedang'] / $p['total']) * 100, 1) : 0;
                    $pR = $p['total'] > 0 ? round(($p['rendah'] / $p['total']) * 100, 1) : 0;
                    $pB = max(0, 100 - $pT - $pS - $pR);
                @endphp
                <div class="adm-bar-row" onclick="dashFilterByProdi(@js($p['label']))">
                    <div class="flex items-baseline justify-between gap-2 text-xs">
                        <span class="font-medium truncate">{{ $p['label'] }}</span>
                        <span class="mono text-ink-2" style="white-space:nowrap;">
                            <strong>{{ $p['total'] }} mhs</strong> &middot;
                            <span style="color:var(--bad);">{{ $p['tinggi'] }}T</span>
                            <span style="color:var(--warn);">{{ $p['sedang'] }}S</span>
                            <span style="color:var(--ok);">{{ $p['rendah'] }}R</span>
                        </span>
                    </div>
                    <div class="adm-bar-track mt-1.5">
                        <div class="adm-bar-fill" style="width:{{ $barW }}%;">
                            @if ($pT > 0)<div style="width:{{ $pT }}%; background:var(--bad);"></div>@endif
                            @if ($pS > 0)<div style="width:{{ $pS }}%; background:var(--warn);"></div>@endif
                            @if ($pR > 0)<div style="width:{{ $pR }}%; background:var(--ok);"></div>@endif
                            @if ($pB > 0)<div style="width:{{ $pB }}%; background:var(--line-strong);"></div>@endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-ink-2">Belum ada data mahasiswa.</p>
            @endforelse
        </div>

        {{-- Tab 2: Angkatan --}}
        <div data-chart-panel="angkatan" class="mt-4 space-y-2" style="display:none;">
            @forelse ($angkatanStats as $a)
                @php
                    $barW = round(($a['total'] / $maxAngkatanTotal) * 100, 1);
                    $aT = $a['total'] > 0 ? round(($a['tinggi'] / $a['total']) * 100, 1) : 0;
                    $aS = $a['total'] > 0 ? round(($a['sedang'] / $a['total']) * 100, 1) : 0;
                    $aR = $a['total'] > 0 ? round(($a['rendah'] / $a['total']) * 100, 1) : 0;
                    $aB = max(0, 100 - $aT - $aS - $aR);
                @endphp
                <div class="adm-bar-row" onclick="dashFilterByAngkatan(@js($a['label']))">
                    <div class="flex items-baseline justify-between gap-2 text-xs">
                        <span class="font-medium">{{ $a['label'] === 'Tidak diketahui' ? $a['label'] : 'Angkatan '.$a['label'] }}</span>
                        <span class="mono text-ink-2" style="white-space:nowrap;">
                            <strong>{{ $a['total'] }} mhs</strong> &middot;
                            <span style="color:var(--bad);">{{ $a['tinggi'] }}T</span>
                            <span style="color:var(--warn);">{{ $a['sedang'] }}S</span>
                            <span style="color:var(--ok);">{{ $a['rendah'] }}R</span>
                        </span>
                    </div>
                    <div class="adm-bar-track mt-1.5">
                        <div class="adm-bar-fill" style="width:{{ $barW }}%;">
                            @if ($aT > 0)<div style="width:{{ $aT }}%; background:var(--bad);"></div>@endif
                            @if ($aS > 0)<div style="width:{{ $aS }}%; background:var(--warn);"></div>@endif
                            @if ($aR > 0)<div style="width:{{ $aR }}%; background:var(--ok);"></div>@endif
                            @if ($aB > 0)<div style="width:{{ $aB }}%; background:var(--line-strong);"></div>@endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-ink-2">Belum ada data mahasiswa.</p>
            @endforelse
        </div>

        {{-- Tab 3: Beban DPA --}}
        <div data-chart-panel="dpa" class="mt-4 space-y-2" style="display:none;">
            @forelse ($dpaStats as $ds)
                @php
                    $barW = round(($ds['total'] / $maxDpaTotal) * 100, 1);
                    $dT = $ds['total'] > 0 ? round(($ds['tinggi'] / $ds['total']) * 100, 1) : 0;
                    $dS = $ds['total'] > 0 ? round(($ds['sedang'] / $ds['total']) * 100, 1) : 0;
                    $dR = $ds['total'] > 0 ? round(($ds['rendah'] / $ds['total']) * 100, 1) : 0;
                    $dB = max(0, 100 - $dT - $dS - $dR);
                @endphp
                <div class="adm-bar-row" onclick="dashFilterByDpa('{{ $ds['dpa']->id }}')">
                    <div class="flex items-baseline justify-between gap-2 text-xs">
                        <span class="font-medium truncate">{{ $ds['dpa']->name }}</span>
                        <span class="mono text-ink-2" style="white-space:nowrap;">
                            <strong>{{ $ds['total'] }} bimbingan</strong> ({{ $ds['consented'] }} izin)
                        </span>
                    </div>
                    <div class="adm-bar-track mt-1.5">
                        <div class="adm-bar-fill" style="width:{{ $barW }}%;">
                            @if ($dT > 0)<div style="width:{{ $dT }}%; background:var(--bad);"></div>@endif
                            @if ($dS > 0)<div style="width:{{ $dS }}%; background:var(--warn);"></div>@endif
                            @if ($dR > 0)<div style="width:{{ $dR }}%; background:var(--ok);"></div>@endif
                            @if ($dB > 0)<div style="width:{{ $dB }}%; background:var(--line-strong);"></div>@endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-ink-2">Belum ada akun DPA terdaftar.</p>
            @endforelse
        </div>
    </div>
</section>

{{-- BLOK 3: MATRIKS KOHORT GABUNGAN (ANGKATAN x PROGRAM STUDI) --}}
<section class="adm-panel mt-6" aria-labelledby="rekap-kohort">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <div>
            <h2 id="rekap-kohort" class="text-base font-semibold">Pemetaan Kohort: Angkatan &times; Program Studi</h2>
            <p class="mt-0.5 text-xs text-ink-3">Ringkasan risiko gabungan per angkatan dan prodi. Klik <strong>Lihat</strong> untuk langsung menyaring tabel mahasiswa di bawah.</p>
            <p class="adm-mobile-hint">Geser tabel ke samping untuk melihat seluruh kolom &rarr;</p>
        </div>
        <span class="mono text-xs text-ink-2">{{ $cohortStats->count() }} kohort aktif</span>
    </div>

    @if ($cohortStats->isEmpty())
        <p class="mt-4 text-sm text-ink-2">Belum ada data mahasiswa.</p>
    @else
        <div class="adm-scroll mt-4">
            <table class="tbl adm-table w-full text-xs">
                <thead>
                    <tr>
                        <th>Angkatan</th>
                        <th>Program Studi</th>
                        <th style="min-width:110px;">Komposisi Risiko</th>
                        <th class="num">Total</th>
                        <th class="num">Tinggi</th>
                        <th class="num">Sedang</th>
                        <th class="num">Rendah</th>
                        <th class="num">Belum</th>
                        <th class="num">IPS</th>
                        <th class="num">Hadir</th>
                        <th><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cohortStats as $c)
                        @php
                            $cT = $c['total'] > 0 ? round(($c['tinggi'] / $c['total']) * 100, 1) : 0;
                            $cS = $c['total'] > 0 ? round(($c['sedang'] / $c['total']) * 100, 1) : 0;
                            $cR = $c['total'] > 0 ? round(($c['rendah'] / $c['total']) * 100, 1) : 0;
                            $cB = max(0, 100 - $cT - $cS - $cR);
                        @endphp
                        <tr>
                            <td class="mono font-semibold" style="white-space:nowrap;">
                                {{ $c['angkatan'] === 'Tidak diketahui' ? $c['angkatan'] : 'Angkatan '.$c['angkatan'] }}
                            </td>
                            <td class="font-medium">{{ $c['program_studi'] }}</td>
                            <td>
                                <div class="adm-bar-track" style="height:8px;">
                                    <div class="adm-bar-fill" style="width:100%;">
                                        @if ($cT > 0)<div style="width:{{ $cT }}%; background:var(--bad);"></div>@endif
                                        @if ($cS > 0)<div style="width:{{ $cS }}%; background:var(--warn);"></div>@endif
                                        @if ($cR > 0)<div style="width:{{ $cR }}%; background:var(--ok);"></div>@endif
                                        @if ($cB > 0)<div style="width:{{ $cB }}%; background:var(--line-strong);"></div>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="num mono font-semibold">{{ $c['total'] }}</td>
                            <td class="num mono font-semibold" style="color:var(--bad);">{{ $c['tinggi'] }}</td>
                            <td class="num mono font-semibold" style="color:var(--warn);">{{ $c['sedang'] }}</td>
                            <td class="num mono font-semibold" style="color:var(--ok);">{{ $c['rendah'] }}</td>
                            <td class="num mono text-ink-3">{{ $c['belum'] }}</td>
                            <td class="num mono">{{ $c['avg_ips'] !== null ? number_format($c['avg_ips'], 2) : '-' }}</td>
                            <td class="num mono">{{ $c['avg_attendance'] !== null ? number_format($c['avg_attendance'], 1).'%' : '-' }}</td>
                            <td class="text-right" style="white-space:nowrap;">
                                <button type="button" class="btn btn-quiet btn-sm"
                                    onclick="dashFilterByCohort(@js($c['angkatan']), @js($c['program_studi']))">
                                    Lihat &rarr;
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

{{-- BLOK 4: REKAP BEBAN & CAKUPAN DPA --}}
<section class="adm-panel mt-6" aria-labelledby="rekap-dpa">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <div>
            <h2 id="rekap-dpa" class="text-base font-semibold">Rekap Dosen Pembimbing Akademik (DPA)</h2>
            <p class="mt-0.5 text-xs text-ink-3">Beban bimbingan, cakupan izin akses mahasiswa, dan sebaran tingkat risiko per DPA.</p>
        </div>
        <span class="mono text-xs text-ink-2">{{ $totals['dpas'] }} DPA terdaftar</span>
    </div>

    @if ($dpaStats->isEmpty())
        <p class="mt-4 text-sm text-ink-2">Belum ada akun DPA terdaftar.</p>
    @else
        <div class="adm-scroll mt-4">
            <table class="tbl adm-table w-full text-xs">
                <thead>
                    <tr>
                        <th>Nama DPA</th>
                        <th>NIDN / ID</th>
                        <th>Status</th>
                        <th class="num">Bimbingan</th>
                        <th class="num">Izin Aktif</th>
                        <th class="num">Tinggi</th>
                        <th class="num">Sedang</th>
                        <th class="num">Rendah</th>
                        <th class="num">Belum</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dpaStats as $ds)
                        <tr>
                            <td class="font-medium">
                                <button type="button" class="text-accent hover:underline" style="background:none;border:none;padding:0;cursor:pointer;font-weight:500;"
                                    onclick="dashFilterByDpa('{{ $ds['dpa']->id }}')">
                                    {{ $ds['dpa']->name }}
                                </button>
                            </td>
                            <td class="mono text-ink-3">{{ $ds['dpa']->username }}</td>
                            <td>{{ $ds['dpa']->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td class="num mono font-semibold">{{ $ds['total'] }}</td>
                            <td class="num mono">{{ $ds['consented'] }}</td>
                            <td class="num mono font-semibold" style="color:var(--bad);">{{ $ds['tinggi'] }}</td>
                            <td class="num mono font-semibold" style="color:var(--warn);">{{ $ds['sedang'] }}</td>
                            <td class="num mono font-semibold" style="color:var(--ok);">{{ $ds['rendah'] }}</td>
                            <td class="num mono text-ink-3">{{ $ds['belum'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

{{-- BLOK 5: TABEL INTERAKTIF ANALISIS RISIKO SELURUH MAHASISWA --}}
<section id="tabel-mahasiswa" class="adm-panel mt-6" aria-labelledby="judul-tabel-mhs">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 id="judul-tabel-mhs" class="text-lg font-semibold">Data Analisis Risiko Semua Mahasiswa</h2>
            <p class="mt-0.5 text-xs text-ink-2">
                Menampilkan <strong id="visible-count">{{ $filteredStudents->count() }}</strong> dari {{ $totals['students'] }} mahasiswa (filter langsung tanpa muat ulang halaman).
            </p>
        </div>
        <button id="btn-reset-filter" type="button" class="btn btn-quiet btn-sm" onclick="dashResetFilters()" style="{{ array_filter($filters) === [] ? 'display:none;' : '' }}">
            Reset Filter
        </button>
    </div>

    {{-- Chip Filter Cepat Tingkat Risiko --}}
    <div class="adm-chip-row mt-4" role="group" aria-label="Saring cepat tingkat risiko">
        <button type="button" class="adm-chip {{ $filters['tingkat'] === '' ? 'is-active' : '' }}" data-quick-level="" onclick="dashFilterByLevel('')">Semua ({{ $riskCounts['semua'] }})</button>
        <button type="button" class="adm-chip {{ $filters['tingkat'] === 'tinggi' ? 'is-active' : '' }}" data-quick-level="tinggi" onclick="dashFilterByLevel('tinggi')">Tinggi ({{ $riskCounts['tinggi'] }})</button>
        <button type="button" class="adm-chip {{ $filters['tingkat'] === 'sedang' ? 'is-active' : '' }}" data-quick-level="sedang" onclick="dashFilterByLevel('sedang')">Sedang ({{ $riskCounts['sedang'] }})</button>
        <button type="button" class="adm-chip {{ $filters['tingkat'] === 'rendah' ? 'is-active' : '' }}" data-quick-level="rendah" onclick="dashFilterByLevel('rendah')">Rendah ({{ $riskCounts['rendah'] }})</button>
        <button type="button" class="adm-chip {{ $filters['tingkat'] === 'belum' ? 'is-active' : '' }}" data-quick-level="belum" onclick="dashFilterByLevel('belum')">Belum ({{ $riskCounts['belum'] }})</button>
    </div>

    <form id="dash-filter-form" method="GET" action="{{ route('admin.dasbor') }}#tabel-mahasiswa" class="adm-filter-grid mt-4">
        <div>
            <label for="f-q" class="label text-xs">Cari nama / NIM</label>
            <input id="f-q" name="q" type="text" value="{{ $filters['q'] }}" placeholder="Ketik nama atau NIM..." class="input h-9 text-sm" oninput="dashApplyFilters()">
        </div>

        <div>
            <label for="f-prodi" class="label text-xs">Program Studi</label>
            <select id="f-prodi" name="prodi" class="input h-9 text-xs" onchange="dashApplyFilters()">
                <option value="">Semua prodi</option>
                @foreach ($prodiOptions as $po)
                    <option value="{{ $po }}" @selected($filters['prodi'] === $po)>{{ $po }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="f-angkatan" class="label text-xs">Angkatan</label>
            <select id="f-angkatan" name="angkatan" class="input h-9 text-xs" onchange="dashApplyFilters()">
                <option value="">Semua angkatan</option>
                @foreach ($angkatanOptions as $ao)
                    <option value="{{ $ao }}" @selected($filters['angkatan'] === $ao)>{{ $ao === 'Tidak diketahui' ? $ao : 'Angkatan '.$ao }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="f-tingkat" class="label text-xs">Tingkat Risiko</label>
            <select id="f-tingkat" name="tingkat" class="input h-9 text-xs" onchange="dashApplyFilters()">
                <option value="">Semua tingkat</option>
                <option value="tinggi" @selected($filters['tingkat'] === 'tinggi')>Tinggi</option>
                <option value="sedang" @selected($filters['tingkat'] === 'sedang')>Sedang</option>
                <option value="rendah" @selected($filters['tingkat'] === 'rendah')>Rendah</option>
                <option value="belum" @selected($filters['tingkat'] === 'belum')>Belum dihitung</option>
            </select>
        </div>

        <div>
            <label for="f-dpa" class="label text-xs">DPA</label>
            <select id="f-dpa" name="dpa" class="input h-9 text-xs" onchange="dashApplyFilters()">
                <option value="">Semua DPA</option>
                <option value="belum" @selected($filters['dpa'] === 'belum')>Belum ditetapkan</option>
                @foreach ($dpas as $d)
                    <option value="{{ $d->id }}" @selected($filters['dpa'] === (string) $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sm h-9 w-full">
            <x-icon name="magnifying-glass" /> Terapkan
        </button>
    </form>

    <p class="adm-mobile-hint">Geser tabel ke samping untuk melihat seluruh kolom &rarr;</p>

    @if ($filteredStudents->isEmpty())
        <p class="mt-6 text-sm text-ink-2">Tidak ada mahasiswa yang cocok dengan filter saat ini.</p>
    @else
        <div class="adm-scroll mt-5">
            <table class="tbl adm-table w-full text-xs" style="min-width:66rem;">
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Prodi &amp; Angkatan</th>
                        <th>DPA &amp; Izin</th>
                        <th>Risiko</th>
                        <th class="num">Skor</th>
                        <th class="num">Sem</th>
                        <th class="num">IPS</th>
                        <th class="num">Hadir</th>
                        <th class="num">Pengayaan</th>
                        <th><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody id="student-table-body">
                    @foreach ($filteredStudents as $idx => $row)
                        @php
                            $stu = $row['student'];
                            $scorePct = $row['score'] !== null ? min(100, max(0, round(($row['score'] / 10) * 100))) : 0;
                            $enrPct = min(100, round(($row['enrichment_converted_sks'] / 20) * 100));
                        @endphp
                        <tr data-student-row
                            data-search="{{ mb_strtolower($stu->name.' '.$stu->username, 'UTF-8') }}"
                            data-prodi="{{ $row['program_studi'] }}"
                            data-angkatan="{{ $row['angkatan'] }}"
                            data-level="{{ $row['level'] }}"
                            data-dpa="{{ $stu->dpa_id ? (string) $stu->dpa_id : 'belum' }}"
                            data-idx="{{ $idx }}">
                            <td>
                                <p class="font-medium text-sm">{{ $stu->name }}</p>
                                <p class="mono text-ink-3">{{ $stu->username }}@if (! $stu->is_active) &middot; <span style="color:var(--bad);">Nonaktif</span>@endif</p>
                            </td>
                            <td>
                                <p class="font-medium">{{ $row['program_studi'] }}</p>
                                <p class="text-ink-3">{{ $row['angkatan'] === 'Tidak diketahui' ? 'Angkatan -' : 'Angkatan '.$row['angkatan'] }}</p>
                            </td>
                            <td>
                                <p>{{ $stu->dpa?->name ?? 'Belum ditetapkan' }}</p>
                                <p class="text-ink-3">{{ $row['consent_granted'] ? 'Izin DPA aktif' : 'Belum memberi izin' }}</p>
                            </td>
                            <td style="white-space:nowrap;">
                                @if ($row['level'] === 'belum')
                                    <span class="rounded border border-line-strong bg-bg px-2 py-0.5 text-ink-3">Belum</span>
                                @else
                                    <x-risk :level="$row['level']" />
                                @endif
                            </td>
                            <td class="num mono font-semibold">{{ $row['score'] !== null ? number_format($row['score'], 2) : '-' }}</td>
                            <td class="num mono">{{ $row['semester'] ?? '-' }}</td>
                            <td class="num mono">{{ $row['ips'] !== null ? number_format($row['ips'], 2) : '-' }}</td>
                            <td class="num mono">{{ $row['attendance'] !== null ? number_format($row['attendance'], 1).'%' : '-' }}</td>
                            <td class="num mono">{{ $row['enrichment_converted_sks'] }}/20</td>
                            <td class="text-right" style="white-space:nowrap;">
                                <button type="button" class="btn btn-quiet btn-sm" onclick="dashToggleDetail({{ $idx }})">Detail</button>
                                <a href="{{ route('admin.akun.ubah', $stu) }}" class="btn btn-quiet btn-sm">Ubah</a>
                            </td>
                        </tr>
                        <tr id="detail-row-{{ $idx }}" class="adm-detail-row" data-detail-for="{{ $idx }}">
                            <td colspan="10" style="padding: 0.9rem 1rem;">
                                <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));">
                                    <div>
                                        <p class="text-ink-3" style="font-size:11px;">Faktor Risiko Utama</p>
                                        <p class="mt-1 font-medium text-ink">{{ $row['top_factor'] ?? 'Belum ada faktor risiko tercatat' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-ink-3" style="font-size:11px;">Status Tindak Lanjut DPA</p>
                                        <p class="mt-1 font-medium text-ink">{{ \App\Models\StudentSetting::followUpLabelFor($row['follow_up_status']) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-ink-3" style="font-size:11px;">Skor Risiko ({{ $row['score'] !== null ? number_format($row['score'], 2).'/10' : '-' }})</p>
                                        <div class="adm-mini-bar" style="margin-top:0.45rem;">
                                            <div class="adm-mini-fill" style="width:{{ $scorePct }}%; background:{{ $row['level'] === 'tinggi' ? 'var(--bad)' : ($row['level'] === 'sedang' ? 'var(--warn)' : 'var(--ok)') }};"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-ink-3" style="font-size:11px;">Progres 20 SKS Pengayaan ({{ $row['enrichment_converted_sks'] }}/20 SKS)</p>
                                        <div class="adm-mini-bar" style="margin-top:0.45rem;">
                                            <div class="adm-mini-fill" style="width:{{ $enrPct }}%; background:var(--accent);"></div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p id="empty-filter-msg" class="mt-5 text-sm text-ink-2" style="display:none;">Tidak ada mahasiswa yang cocok dengan filter saat ini.</p>
    @endif
</section>

<script>
const dashExportBase = @js(route('admin.dasbor.ekspor'));

function dashHoverDonut(val, lbl) {
    const v = document.getElementById('donut-center-val');
    const l = document.getElementById('donut-center-lbl');
    if (v) v.textContent = val;
    if (l) l.textContent = lbl;
}

function dashSwitchTab(tab) {
    document.querySelectorAll('[data-chart-tab]').forEach(btn => {
        btn.classList.toggle('is-active', btn.dataset.chartTab === tab);
    });
    document.querySelectorAll('[data-chart-panel]').forEach(panel => {
        panel.style.display = panel.dataset.chartPanel === tab ? '' : 'none';
    });
}

function dashScrollToTable() {
    const el = document.getElementById('tabel-mahasiswa');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function dashFilterByLevel(level) {
    const sel = document.getElementById('f-tingkat');
    if (sel) sel.value = level;
    dashApplyFilters();
    dashScrollToTable();
}

function dashFilterByProdi(prodi) {
    const sel = document.getElementById('f-prodi');
    if (sel) sel.value = prodi;
    dashApplyFilters();
    dashScrollToTable();
}

function dashFilterByAngkatan(angkatan) {
    const sel = document.getElementById('f-angkatan');
    if (sel) sel.value = angkatan;
    dashApplyFilters();
    dashScrollToTable();
}

function dashFilterByDpa(dpaId) {
    const sel = document.getElementById('f-dpa');
    if (sel) sel.value = dpaId;
    dashApplyFilters();
    dashScrollToTable();
}

function dashFilterByCohort(angkatan, prodi) {
    const aSel = document.getElementById('f-angkatan');
    const pSel = document.getElementById('f-prodi');
    if (aSel) aSel.value = angkatan;
    if (pSel) pSel.value = prodi;
    dashApplyFilters();
    dashScrollToTable();
}

function dashResetFilters() {
    ['f-q', 'f-prodi', 'f-angkatan', 'f-tingkat', 'f-dpa'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    dashApplyFilters();
}

function dashToggleDetail(idx) {
    const row = document.getElementById('detail-row-' + idx);
    if (row) row.classList.toggle('is-open');
}

function dashApplyFilters() {
    const q = (document.getElementById('f-q')?.value || '').trim().toLowerCase();
    const prodi = document.getElementById('f-prodi')?.value || '';
    const angkatan = document.getElementById('f-angkatan')?.value || '';
    const level = document.getElementById('f-tingkat')?.value || '';
    const dpa = document.getElementById('f-dpa')?.value || '';

    let visible = 0;
    document.querySelectorAll('[data-student-row]').forEach(tr => {
        const ok = (!q || tr.dataset.search.includes(q))
            && (!prodi || tr.dataset.prodi === prodi)
            && (!angkatan || tr.dataset.angkatan === angkatan)
            && (!level || tr.dataset.level === level)
            && (!dpa || tr.dataset.dpa === dpa);
        tr.style.display = ok ? '' : 'none';
        const det = document.getElementById('detail-row-' + tr.dataset.idx);
        if (det && !ok) det.classList.remove('is-open');
        if (ok) visible++;
    });

    const cnt = document.getElementById('visible-count');
    if (cnt) cnt.textContent = visible;

    const emptyMsg = document.getElementById('empty-filter-msg');
    if (emptyMsg) emptyMsg.style.display = visible === 0 ? '' : 'none';

    const hasFilter = Boolean(q || prodi || angkatan || level || dpa);
    const resetBtn = document.getElementById('btn-reset-filter');
    if (resetBtn) resetBtn.style.display = hasFilter ? '' : 'none';

    document.querySelectorAll('[data-quick-level]').forEach(chip => {
        chip.classList.toggle('is-active', chip.dataset.quickLevel === level);
    });
    document.querySelectorAll('[data-legend-level]').forEach(btn => {
        btn.classList.toggle('is-active', level !== '' && btn.dataset.legendLevel === level);
    });

    const params = new URLSearchParams();
    if (q) params.set('q', q);
    if (prodi) params.set('prodi', prodi);
    if (angkatan) params.set('angkatan', angkatan);
    if (level) params.set('tingkat', level);
    if (dpa) params.set('dpa', dpa);
    const exportBtn = document.getElementById('btn-export-csv');
    if (exportBtn) {
        exportBtn.href = params.toString() ? `${dashExportBase}?${params.toString()}` : dashExportBase;
    }
}
</script>
@endsection