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
        padding: 0.15rem 0 1.25rem;
        border-bottom: 1px solid var(--line);
    }
    .adm-meta-line {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        font-size: 12px;
        color: var(--ink-3);
    }
    .adm-dot { width: 6px; height: 6px; border-radius: 9999px; background: var(--ok); display: inline-block; }
    .adm-kpi-grid { display: grid; gap: 1rem; grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .adm-kpi {
        border-radius: 14px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: 1.2rem 1.35rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 0.65rem;
        min-width: 0;
        transition: border-color 0.15s ease, background 0.15s ease;
    }
    .adm-kpi:hover { border-color: var(--line-strong); }
    .adm-kpi-clickable { cursor: pointer; text-align: left; }
    .adm-kpi-clickable:hover { background: color-mix(in srgb, var(--ink) 2%, var(--surface)); }

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
    .adm-group-badge {
        display: inline-flex; align-items: center; padding: 0.16rem 0.5rem; border-radius: 6px;
        font-size: 11px; font-weight: 600; background: var(--bg); border: 1px solid var(--line);
        color: var(--ink-2); white-space: nowrap;
    }
    .adm-risk-summary { display: flex; flex-wrap: wrap; gap: 0.45rem; margin-top: 0.35rem; font-size: 11px; color: var(--ink-3); }
    .adm-cohort-row { cursor: pointer; transition: background 0.15s ease; }
    .adm-cohort-row:hover { background: color-mix(in srgb, var(--accent) 4%, var(--bg)); }
    .adm-table th, .adm-table td { padding: 0.72rem 0.75rem; vertical-align: middle; }
    .adm-table th.num, .adm-table td.num { white-space: nowrap; text-align: right; }
    .adm-detail-row { display: none; background: color-mix(in srgb, var(--accent) 4%, var(--bg)); }
    .adm-detail-row.is-open { display: table-row; }
    .adm-mobile-hint { display: none; }

    .adm-section-gap { margin-top: 1.5rem; }
    .adm-panel-airy { padding: 1.5rem 1.7rem; }
    .adm-wave-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.9rem; margin-bottom: 1.15rem; }
    .adm-wave-stats { display: flex; flex-wrap: wrap; align-items: baseline; gap: 1.4rem; margin-bottom: 1rem; }
    .adm-wave-stat-sep { padding-left: 1.4rem; border-left: 1px solid var(--line); }
    .adm-wave-wrap { position: relative; width: 100%; }
    .adm-wave-scroll { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 0.25rem; }
    .adm-wave-svg { width: 100%; height: auto; display: block; overflow: visible; }
    .adm-wave-dot { cursor: pointer; transition: stroke-width 0.15s ease; }
    .adm-wave-dot:hover { stroke-width: 3.5; }
    .adm-wave-tip {
        position: absolute; pointer-events: none; z-index: 25; display: none;
        min-width: 175px; max-width: 230px; padding: 0.6rem 0.75rem; border-radius: 10px;
        background: var(--surface); border: 1px solid var(--line-strong);
        box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.2);
        font-size: 11.5px; line-height: 1.45; color: var(--ink);
    }
    .adm-wave-mobile-bar {
        display: none; margin-top: 0.65rem; padding: 0.55rem 0.75rem; border-radius: 9px;
        border: 1px solid var(--line); background: var(--bg);
        font-size: 12px; color: var(--ink-2); align-items: center; justify-content: space-between; gap: 0.5rem;
    }
    .adm-ready-grid { display: grid; gap: 0.9rem; grid-template-columns: repeat(5, minmax(0, 1fr)); margin-top: 1.1rem; }
    .adm-fu-grid { display: grid; gap: 0.9rem; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-top: 1.1rem; }
    .adm-ready-card {
        padding: 0.9rem 1rem; border-radius: 11px; border: 1px solid var(--line);
        background: var(--bg); display: flex; flex-direction: column; gap: 0.5rem; min-width: 0;
    }
    .adm-ready-top { display: flex; align-items: baseline; justify-content: space-between; gap: 0.4rem; }
    @media (max-width: 960px) {
        .adm-analytics-grid { grid-template-columns: 1fr; }
        .adm-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .adm-panel-airy { padding: 1.2rem; }
        .adm-ready-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; }
        .adm-fu-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; }
    }
    @media (max-width: 640px) {
        .adm-panel-airy { padding: 1.05rem; }
        .adm-wave-svg { min-width: 500px; }
        .adm-wave-tip { display: none !important; }
        .adm-wave-mobile-bar { display: flex; }
        .adm-wave-stats { gap: 1rem; }
        .adm-wave-stat-sep { padding-left: 1rem; }
        .adm-ready-card { padding: 0.75rem 0.85rem; gap: 0.4rem; }
    }
    @media (max-width: 340px) {
        .adm-ready-grid, .adm-fu-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .adm-hero { padding: 0 0 1rem; }
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

{{-- HEADER RINGKAS ANTI-SLOP --}}
<section class="adm-hero" aria-label="Ringkasan akademik">
    <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1rem;">
        <div>
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-ink">Dasboard Akademik</h1>
            <p class="mt-1 text-sm text-ink-2">
                {{ $totals['students'] }} mahasiswa dan {{ $totals['dpas'] }} DPA terdaftar &middot;
                @if ($attentionCount > 0)
                    <strong style="color:{{ $riskCounts['tinggi'] > 0 ? 'var(--bad)' : 'var(--warn)' }};">{{ $attentionCount }} perlu perhatian</strong> ({{ $attentionPct }}%).
                @else
                    semua mahasiswa teranalisis dalam kondisi stabil.
                @endif
            </p>
        </div>

        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.6rem;">
            <a id="btn-export-csv" href="{{ route('admin.dasbor.ekspor', array_filter($filters)) }}" class="btn btn-quiet btn-sm">
                <x-icon name="download-simple" /> Unduh CSV
            </a>
            <a href="{{ route('admin.akun.baru') }}" class="btn btn-primary btn-sm">
                <x-icon name="plus" /> Tambah Akun
            </a>
        </div>
    </div>
</section>

{{-- BLOK 1: 4 KARTU RINGKASAN TENANG & PROPORSIONAL --}}
<section class="adm-kpi-grid adm-section-gap" aria-label="Indikator utama">
    <div class="adm-kpi">
        <p class="text-xs font-medium text-ink-3">Total Mahasiswa</p>
        <p class="mono text-3xl font-semibold leading-none text-ink">{{ $totals['students'] }}</p>
        <p class="text-xs text-ink-2">{{ $totals['students_active'] }} aktif &middot; <strong>{{ $analyzedPct }}%</strong> sudah dianalisis</p>
    </div>

    <div class="adm-kpi">
        <p class="text-xs font-medium text-ink-3">Dosen Pembimbing</p>
        <p class="mono text-3xl font-semibold leading-none text-ink">{{ $totals['dpas'] }} <span class="text-sm font-normal text-ink-3">DPA</span></p>
        <p class="text-xs text-ink-2">
            {{ $totals['dpas_active'] }} aktif &middot; {{ $totals['admins'] }} admin
            @if ($totals['students_without_dpa'] > 0)
                &middot; <span style="color:var(--warn);">{{ $totals['students_without_dpa'] }} tanpa DPA</span>
            @endif
        </p>
    </div>

    <div class="adm-kpi adm-kpi-clickable" onclick="dashFilterByLevel('{{ $riskCounts['tinggi'] > 0 ? 'tinggi' : 'sedang' }}')" title="Klik untuk menyaring mahasiswa yang perlu perhatian">
        <p class="text-xs font-medium text-ink-3">Perlu Perhatian</p>
        <p class="mono text-3xl font-semibold leading-none" style="color:{{ $riskCounts['tinggi'] > 0 ? 'var(--bad)' : ($attentionCount > 0 ? 'var(--warn)' : 'var(--ink)') }};">
            {{ $attentionCount }}
        </p>
        <p class="text-xs text-ink-2">{{ $riskCounts['tinggi'] }} tinggi &middot; {{ $riskCounts['sedang'] }} sedang</p>
    </div>

    <div class="adm-kpi">
        <p class="text-xs font-medium text-ink-3">Terhubung ke DPA</p>
        <p class="mono text-3xl font-semibold leading-none text-ink">{{ $totals['students_consented'] }} <span class="text-sm font-normal text-ink-3">/ {{ $totals['students'] }}</span></p>
        <p class="text-xs text-ink-2">
            {{ $consentPct }}% punya DPA &middot;
            <a href="{{ route('admin.tiket') }}" class="text-accent font-medium hover:underline">{{ $totals['pending_tickets'] }} tiket baru</a>
        </p>
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
                            <strong>{{ $ds['total'] }} bimbingan</strong>
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

{{-- BLOK 2B: GRAFIK GELOMBANG DISTRIBUSI FULL-WIDTH (1 SUMBU, SKALABEL & MOBILE-SAFE) --}}
@php
    $distWaves = $distributionWaves ?? [];

    $buildDistSvg = function (array $series, int $w = 780, int $h = 230) {
        $buckets = $series['buckets'] ?? [];
        $padL = 40; $padR = 26; $padT = 32; $padB = 38;
        $innerPadX = 44;
        $plotW = $w - $padL - $padR;
        $plotH = $h - $padT - $padB;
        $n = count($buckets);

        $counts = array_map(fn ($b) => (int) ($b['count'] ?? 0), $buckets);
        $maxVal = max(1, $counts !== [] ? max($counts) : 1);
        $yMax = $maxVal <= 4 ? 4 : (int) (ceil($maxVal / 4) * 4);
        if ($yMax === $maxVal) {
            $yMax += max(1, (int) ceil($maxVal * 0.25));
        }

        $ticks = [];
        for ($k = 0; $k <= 4; $k++) {
            $ratio = $k / 4.0;
            $gy = round($padT + (1.0 - $ratio) * $plotH, 1);
            $ticks[] = ['y' => $gy, 'val' => (int) round($ratio * $yMax)];
        }

        $coords = [];
        $usableW = max(1, $plotW - ($innerPadX * 2));
        $peakIdx = 0;
        $peakCount = -1;
        foreach ($buckets as $i => $b) {
            $x = $n > 1 ? round($padL + $innerPadX + ($i / ($n - 1)) * $usableW, 1) : round($padL + ($plotW / 2), 1);
            $cnt = (int) ($b['count'] ?? 0);
            if ($cnt > $peakCount) {
                $peakCount = $cnt;
                $peakIdx = $i;
            }
            $y = round($padT + (1.0 - ($cnt / $yMax)) * $plotH, 1);
            $coords[] = ['x' => $x, 'y' => $y, 'label' => $b['label'], 'count' => $cnt];
        }

        $line = '';
        $area = '';
        if ($coords !== []) {
            $line = 'M '.$coords[0]['x'].' '.$coords[0]['y'];
            for ($i = 0; $i < count($coords) - 1; $i++) {
                $p1 = $coords[$i];
                $p2 = $coords[$i + 1];
                $midX = round(($p1['x'] + $p2['x']) / 2, 1);
                $line .= ' C '.$midX.' '.$p1['y'].', '.$midX.' '.$p2['y'].', '.$p2['x'].' '.$p2['y'];
            }
            $bottomY = $padT + $plotH;
            $area = $line.' L '.$coords[count($coords) - 1]['x'].' '.$bottomY.' L '.$coords[0]['x'].' '.$bottomY.' Z';
        }

        return [
            'w' => $w, 'h' => $h, 'padL' => $padL, 'padR' => $padR, 'padT' => $padT, 'padB' => $padB,
            'plotW' => $plotW, 'plotH' => $plotH, 'peakIdx' => $peakIdx,
            'ticks' => $ticks, 'coords' => $coords, 'line' => $line, 'area' => $area,
        ];
    };

    $waveTabs = [
        'ips' => ['title' => 'IPS', 'sub' => 'Rata-rata IPS'],
        'attendance' => ['title' => 'Kehadiran', 'sub' => 'Rata-rata Hadir'],
        'risk' => ['title' => 'Skor Risiko', 'sub' => 'Rata-rata Skor'],
    ];
@endphp

<section class="adm-panel adm-panel-airy adm-section-gap" aria-label="Sebaran akademik mahasiswa">
    <div class="adm-wave-head">
        <div>
            <h2 class="text-base font-semibold">Sebaran Akademik Mahasiswa</h2>
            <p class="mt-0.5 text-xs text-ink-3">Jumlah mahasiswa pada tiap rentang nilai.</p>
        </div>

        <div class="adm-tabs" role="tablist" aria-label="Pilih metrik sebaran">
            @foreach ($waveTabs as $tabKey => $tabMeta)
                <button type="button" class="adm-tab {{ $tabKey === 'ips' ? 'is-active' : '' }}" data-wave-tab="{{ $tabKey }}" onclick="dashSwitchWaveTab('{{ $tabKey }}')">
                    {{ $tabMeta['title'] }}
                </button>
            @endforeach
        </div>
    </div>

    @foreach ($waveTabs as $tabKey => $tabMeta)
        @php
            $series = $distWaves[$tabKey] ?? ['buckets' => [], 'total' => 0, 'avg' => '-', 'highlight' => '-', 'color' => 'var(--ok)'];
            $svg = $buildDistSvg($series);
            $color = $series['color'];
            $totalN = max(0, (int) ($series['total'] ?? 0));
            $peakPt = $svg['coords'][$svg['peakIdx']] ?? null;
            $peakPct = ($peakPt && $totalN > 0) ? round(($peakPt['count'] / $totalN) * 100, 1) : 0;
        @endphp
        <div data-wave-panel="{{ $tabKey }}" style="{{ $tabKey === 'ips' ? '' : 'display:none;' }}">
            @if ($totalN === 0)
                <p class="mt-6 text-sm text-ink-2">Belum ada data mahasiswa yang dianalisis.</p>
            @else
                <div class="adm-wave-stats">
                    <div>
                        <span class="text-xs text-ink-3">{{ $tabMeta['sub'] }}</span>
                        <div class="mono text-xl font-semibold text-ink" style="margin-top:2px;">{{ $series['avg'] }}</div>
                    </div>
                    <div class="adm-wave-stat-sep">
                        <span class="text-xs text-ink-3">Ringkasan</span>
                        <div class="text-sm font-medium" style="color:{{ $color }};margin-top:3px;">{{ $series['highlight'] }}</div>
                    </div>
                </div>

                <div class="adm-wave-wrap" id="adm-wave-box-{{ $tabKey }}">
                    <div class="adm-wave-scroll">
                        <svg class="adm-wave-svg" viewBox="0 0 {{ $svg['w'] }} {{ $svg['h'] }}" role="img" aria-label="Grafik sebaran {{ $tabMeta['title'] }} mahasiswa">
                            <defs>
                                <linearGradient id="distGrad_{{ $tabKey }}" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="{{ $color }}" stop-opacity="0.24" />
                                    <stop offset="100%" stop-color="{{ $color }}" stop-opacity="0.01" />
                                </linearGradient>
                            </defs>

                            {{-- Grid horizontal sederhana (sumbu Y = jumlah mahasiswa) --}}
                            @foreach ($svg['ticks'] as $tk)
                                <line x1="{{ $svg['padL'] }}" y1="{{ $tk['y'] }}" x2="{{ $svg['padL'] + $svg['plotW'] }}" y2="{{ $tk['y'] }}"
                                      stroke="var(--line)" stroke-width="1" stroke-dasharray="3 4" />
                                <text x="{{ $svg['padL'] - 10 }}" y="{{ $tk['y'] + 4 }}" text-anchor="end" font-size="12" fill="var(--ink-3)" class="mono">{{ $tk['val'] }}</text>
                            @endforeach

                            {{-- Area & kurva gelombang tunggal --}}
                            <path d="{{ $svg['area'] }}" fill="url(#distGrad_{{ $tabKey }})" />
                            <path d="{{ $svg['line'] }}" fill="none" stroke="{{ $color }}" stroke-width="3" stroke-linecap="round" />

                            {{-- Titik data, angka langsung di atas titik, dan label rentang di bawah --}}
                            @foreach ($svg['coords'] as $cIdx => $pt)
                                @php $pct = $totalN > 0 ? round(($pt['count'] / $totalN) * 100, 1) : 0; @endphp
                                <text x="{{ $pt['x'] }}" y="{{ $pt['y'] - 12 }}" text-anchor="middle" font-size="13" font-weight="600"
                                      fill="{{ $pt['count'] > 0 ? 'var(--ink)' : 'var(--ink-3)' }}" class="mono">{{ $pt['count'] }}</text>
                                <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="6" fill="{{ $color }}" stroke="var(--surface)" stroke-width="2.4" class="adm-wave-dot"
                                        onmouseenter="dashShowDistTip(event, '{{ $tabKey }}', @js($pt['label']), {{ $pt['count'] }}, {{ $pct }})"
                                        onmousemove="dashShowDistTip(event, '{{ $tabKey }}', @js($pt['label']), {{ $pt['count'] }}, {{ $pct }})"
                                        onclick="dashShowDistTip(event, '{{ $tabKey }}', @js($pt['label']), {{ $pt['count'] }}, {{ $pct }})"
                                        onmouseleave="dashHideDistTip('{{ $tabKey }}')" />
                                <text x="{{ $pt['x'] }}" y="{{ $svg['padT'] + $svg['plotH'] + 26 }}" text-anchor="middle" font-size="12.5" fill="var(--ink-2)">{{ $pt['label'] }}</text>
                            @endforeach
                        </svg>
                    </div>
                    <div id="adm-wave-tip-{{ $tabKey }}" class="adm-wave-tip" role="status" aria-live="polite"></div>

                    {{-- Info bar khusus mobile di bawah grafik agar tidak menimpa kurva --}}
                    @if ($peakPt)
                        <div id="adm-wave-bar-{{ $tabKey }}" class="adm-wave-mobile-bar">
                            <span>Rentang <strong class="text-ink">{{ $peakPt['label'] }}</strong></span>
                            <span class="mono font-semibold text-ink">{{ $peakPt['count'] }} mhs ({{ $peakPct }}%)</span>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endforeach
</section>

{{-- BLOK 2C: RINGKASAN KESIAPAN KELULUSAN & TINDAK LANJUT (PROPORSIONAL FULL-WIDTH) --}}
@php
    $gradList = $graduationReadiness ?? [];
    $pendingProofs = (int) ($pendingSkpiProofs ?? 0);
    $fuList = $followUpStats ?? [];
@endphp
<section class="adm-panel adm-panel-airy adm-section-gap" aria-label="Kesiapan kelulusan dan tindak lanjut">
    <div class="adm-wave-head" style="margin-bottom:0;">
        <div style="display:flex;flex-wrap:wrap;align-items:baseline;gap:0.75rem;">
            <h2 class="text-base font-semibold">Kesiapan Kelulusan</h2>
            @if ($pendingProofs > 0)
                <a href="{{ route('admin.skpi') }}" class="text-xs font-medium text-accent hover:underline">{{ $pendingProofs }} bukti menunggu &rarr;</a>
            @endif
        </div>
        <div class="adm-tabs" role="tablist" aria-label="Pilih tampilan kesiapan">
            <button type="button" class="adm-tab is-active" data-ready-tab="kelulusan" onclick="dashSwitchReadyTab('kelulusan')">SKPI &amp; Pengayaan</button>
            <button type="button" class="adm-tab" data-ready-tab="tindaklanjut" onclick="dashSwitchReadyTab('tindaklanjut')">Tindak Lanjut</button>
        </div>
    </div>

    <div data-ready-panel="kelulusan" class="adm-ready-grid">
        @foreach ($gradList as $g)
            <div class="adm-ready-card" title="{{ $g['hint'] }}">
                <div class="adm-ready-top">
                    <span class="text-xs font-medium text-ink truncate">{{ $g['label'] }}</span>
                    <span class="mono text-xs text-ink-3">{{ $g['done_pct'] }}%</span>
                </div>
                <div class="mono text-base font-semibold text-ink">
                    <span style="color:var(--ok);">{{ $g['done'] }}</span><span class="text-xs font-normal text-ink-3"> / {{ $g['total'] }}</span>
                </div>
                <div class="adm-bar-track" style="height:8px;">
                    <div class="adm-bar-fill" style="width:100%;">
                        @if ($g['done_pct'] > 0)<div style="width:{{ $g['done_pct'] }}%; background:var(--ok);"></div>@endif
                        @if ($g['progress_pct'] > 0)<div style="width:{{ $g['progress_pct'] }}%; background:var(--warn);"></div>@endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div data-ready-panel="tindaklanjut" class="adm-fu-grid" style="display:none;">
        @foreach ($fuList as $fu)
            <div class="adm-ready-card">
                <div class="adm-ready-top">
                    <span class="text-xs font-medium text-ink truncate">{{ $fu['label'] }}</span>
                    <span class="mono text-xs text-ink-3">{{ $fu['pct'] }}%</span>
                </div>
                <div class="mono text-base font-semibold text-ink">
                    {{ $fu['count'] }} <span class="text-xs font-normal text-ink-3">mhs</span>
                    @if ($fu['priority_count'] > 0)
                        <span class="text-xs font-medium" style="color:var(--warn);margin-left:4px;">&middot; {{ $fu['priority_count'] }} prioritas</span>
                    @endif
                </div>
                <div class="adm-bar-track" style="height:8px;">
                    <div class="adm-bar-fill" style="width:{{ max($fu['pct'], $fu['count'] > 0 ? 4 : 0) }}%;">
                        <div style="width:100%; background:{{ $fu['color'] }};"></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- BLOK 3: RINGKASAN PER ANGKATAN & PROGRAM STUDI --}}
<section class="adm-panel adm-panel-airy adm-section-gap" aria-labelledby="rekap-angkatan-prodi">
    <div class="adm-wave-head" style="margin-bottom:1rem;">
        <div>
            <h2 id="rekap-angkatan-prodi" class="text-base font-semibold">Ringkasan per Angkatan &amp; Prodi</h2>
            <p class="mt-0.5 text-xs text-ink-3">Klik baris atau tombol <strong>Lihat</strong> untuk menyaring daftar mahasiswa di bawah.</p>
        </div>
        <span class="mono text-xs text-ink-3">{{ $cohortStats->count() }} kelompok</span>
    </div>

    @if ($cohortStats->isEmpty())
        <p class="mt-4 text-sm text-ink-2">Belum ada data mahasiswa.</p>
    @else
        <div class="adm-scroll">
            <table class="tbl adm-table w-full text-xs" style="table-layout:auto;">
                <thead>
                    <tr>
                        <th style="min-width:210px;">Angkatan &amp; Prodi</th>
                        <th class="num" style="width:76px;">Mhs</th>
                        <th style="min-width:190px;">Kondisi Risiko</th>
                        <th class="num" style="width:78px;">IPS</th>
                        <th class="num" style="width:82px;">Hadir</th>
                        <th class="text-right" style="width:88px;"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cohortStats as $c)
                        @php
                            $cT = $c['total'] > 0 ? round(($c['tinggi'] / $c['total']) * 100, 1) : 0;
                            $cS = $c['total'] > 0 ? round(($c['sedang'] / $c['total']) * 100, 1) : 0;
                            $cR = $c['total'] > 0 ? round(($c['rendah'] / $c['total']) * 100, 1) : 0;
                            $cB = max(0, 100 - $cT - $cS - $cR);
                            $yrLabel = $c['angkatan'] === 'Tidak diketahui' ? 'Tanpa tahun' : $c['angkatan'];
                        @endphp
                        <tr class="adm-cohort-row" onclick="dashFilterByCohort(@js($c['angkatan']), @js($c['program_studi']))">
                            <td>
                                <div style="display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;">
                                    <span class="adm-group-badge mono">{{ $yrLabel }}</span>
                                    <span class="font-medium text-ink">{{ $c['program_studi'] }}</span>
                                </div>
                            </td>
                            <td class="num mono font-semibold text-ink">{{ $c['total'] }}</td>
                            <td>
                                <div class="adm-bar-track" style="height:8px;">
                                    <div class="adm-bar-fill" style="width:100%;">
                                        @if ($cT > 0)<div style="width:{{ $cT }}%; background:var(--bad);"></div>@endif
                                        @if ($cS > 0)<div style="width:{{ $cS }}%; background:var(--warn);"></div>@endif
                                        @if ($cR > 0)<div style="width:{{ $cR }}%; background:var(--ok);"></div>@endif
                                        @if ($cB > 0)<div style="width:{{ $cB }}%; background:var(--line-strong);"></div>@endif
                                    </div>
                                </div>
                                <div class="adm-risk-summary">
                                    @if ($c['tinggi'] > 0)<span style="color:var(--bad);font-weight:600;">{{ $c['tinggi'] }} Tinggi</span>@endif
                                    @if ($c['sedang'] > 0)<span style="color:var(--warn);font-weight:600;">{{ $c['sedang'] }} Sedang</span>@endif
                                    @if ($c['rendah'] > 0)<span style="color:var(--ok);font-weight:500;">{{ $c['rendah'] }} Aman</span>@endif
                                    @if ($c['belum'] > 0)<span>{{ $c['belum'] }} Belum dihitung</span>@endif
                                </div>
                            </td>
                            <td class="num mono">{{ $c['avg_ips'] !== null ? number_format($c['avg_ips'], 2, ',', '.') : '-' }}</td>
                            <td class="num mono">{{ $c['avg_attendance'] !== null ? number_format($c['avg_attendance'], 1, ',', '.').'%' : '-' }}</td>
                            <td class="text-right" style="white-space:nowrap;">
                                <button type="button" class="btn btn-quiet btn-sm"
                                    onclick="event.stopPropagation(); dashFilterByCohort(@js($c['angkatan']), @js($c['program_studi']))">
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
            <p class="mt-0.5 text-xs text-ink-3">Beban bimbingan dan sebaran tingkat risiko per DPA.</p>
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
                        <th class="num">Terpantau</th>
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
                        <th>DPA Pendamping</th>
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
                                <p class="text-ink-3">{{ $row['consent_granted'] ? 'Akses DPA aktif' : 'Menunggu penetapan DPA' }}</p>
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

function dashSwitchWaveTab(tab) {
    document.querySelectorAll('[data-wave-tab]').forEach(btn => {
        btn.classList.toggle('is-active', btn.dataset.waveTab === tab);
    });
    document.querySelectorAll('[data-wave-panel]').forEach(panel => {
        panel.style.display = panel.dataset.wavePanel === tab ? '' : 'none';
    });
}

function dashShowDistTip(evt, key, label, count, pct) {
    const box = document.getElementById('adm-wave-box-' + key);
    const tip = document.getElementById('adm-wave-tip-' + key);
    const bar = document.getElementById('adm-wave-bar-' + key);

    if (bar) {
        bar.innerHTML =
            '<span>Rentang <strong class="text-ink">' + dashEscHtml(label) + '</strong></span>' +
            '<span class="mono font-semibold text-ink">' + count + ' mhs (' + pct + '%)</span>';
    }
    if (!box || !tip) return;

    tip.innerHTML =
        '<div style="font-weight:600;margin-bottom:3px;">Rentang ' + dashEscHtml(label) + '</div>' +
        '<div style="display:flex;justify-content:space-between;gap:12px;"><span>Jumlah</span><strong class="mono">' + count + ' mahasiswa</strong></div>' +
        '<div style="display:flex;justify-content:space-between;gap:12px;"><span>Proporsi</span><strong class="mono">' + pct + '%</strong></div>';

    tip.style.display = 'block';
    const rect = box.getBoundingClientRect();
    let x = evt.clientX - rect.left + 14;
    let y = evt.clientY - rect.top - tip.offsetHeight - 10;
    if (x + tip.offsetWidth > rect.width - 6) x = evt.clientX - rect.left - tip.offsetWidth - 14;
    if (x < 6) x = 6;
    if (y < 4) y = Math.min(rect.height - tip.offsetHeight - 4, evt.clientY - rect.top + 14);
    if (y < 4) y = 4;
    tip.style.left = x + 'px';
    tip.style.top = y + 'px';
}

function dashHideDistTip(key) {
    const tip = document.getElementById('adm-wave-tip-' + key);
    if (tip) tip.style.display = 'none';
}

function dashEscHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function dashSwitchReadyTab(tab) {
    document.querySelectorAll('[data-ready-tab]').forEach(btn => {
        btn.classList.toggle('is-active', btn.dataset.readyTab === tab);
    });
    document.querySelectorAll('[data-ready-panel]').forEach(panel => {
        panel.style.display = panel.dataset.readyPanel === tab ? '' : 'none';
    });
}
</script>
@endsection
