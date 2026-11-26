@extends('layouts.app')

@section('title', 'Mahasiswa bimbingan')

@php
    $notifications = $notifications ?? collect();
    $unreadNotifIds = $unreadNotifIds ?? [];
    $statusCounts = $statusCounts ?? [];
    $riskPcts = $riskPcts ?? ['tinggi' => 0, 'sedang' => 0, 'rendah' => 0, 'belum' => 0];
    $priorityCount = (int) ($priorityCount ?? 0);
    $escalatedCount = (int) ($escalatedCount ?? 0);
    $handledCount = (int) ($handledCount ?? 0);
    $handledPct = (int) ($handledPct ?? 0);
    $semesterStats = $semesterStats ?? collect();
    $topFactors = $topFactors ?? collect();
    $statusFilter = $statusFilter ?? null;

    $visibleTotal = max(0, (int) ($counts['semua'] ?? 0));
    $totalAll = max(0, (int) ($total ?? 0));
    $consentPct = $totalAll > 0 ? round(($visibleTotal / $totalAll) * 100) : 0;
    $attentionCount = (int) ($counts['tinggi'] ?? 0) + (int) ($counts['sedang'] ?? 0);
    $attentionPct = $visibleTotal > 0 ? round(($attentionCount / $visibleTotal) * 100, 1) : 0.0;

    $donutRadius = 56;
    $donutCircumference = 2 * M_PI * $donutRadius;
    $donutSegments = [
        ['key' => 'tinggi', 'label' => 'Risiko Tinggi', 'count' => (int) ($counts['tinggi'] ?? 0), 'pct' => $riskPcts['tinggi'] ?? 0, 'color' => 'var(--bad)'],
        ['key' => 'sedang', 'label' => 'Risiko Sedang', 'count' => (int) ($counts['sedang'] ?? 0), 'pct' => $riskPcts['sedang'] ?? 0, 'color' => 'var(--warn)'],
        ['key' => 'rendah', 'label' => 'Risiko Rendah', 'count' => (int) ($counts['rendah'] ?? 0), 'pct' => $riskPcts['rendah'] ?? 0, 'color' => 'var(--ok)'],
        ['key' => 'belum', 'label' => 'Belum Dihitung', 'count' => (int) ($counts['belum'] ?? 0), 'pct' => $riskPcts['belum'] ?? 0, 'color' => 'var(--line-strong)'],
    ];

    $followUpMeta = [
        'belum_dihubungi' => ['label' => 'Belum dihubungi', 'color' => 'var(--bad)', 'desc' => 'Menunggu kontak awal DPA'],
        'dijadwalkan' => ['label' => 'Dijadwalkan', 'color' => 'var(--warn)', 'desc' => 'Jadwal konsultasi sudah dibuat'],
        'sudah_bimbingan' => ['label' => 'Sudah bimbingan', 'color' => 'var(--accent)', 'desc' => 'Sudah sesi bimbingan & dipantau'],
        'selesai' => ['label' => 'Selesai', 'color' => 'var(--ok)', 'desc' => 'Tindak lanjut semester tuntas'],
    ];

    $maxSemTotal = max(1, (int) ($semesterStats->max('total') ?? 1));
    $maxFactorCount = max(1, (int) ($topFactors->max('count') ?? 1));
@endphp

@section('content')
<style>
    .dpa-hero {
        position: relative;
        overflow: hidden;
        border-radius: 14px;
        border: 1px solid var(--line);
        background: linear-gradient(135deg, color-mix(in srgb, var(--accent) 10%, var(--surface)) 0%, var(--surface) 60%, color-mix(in srgb, var(--ok) 6%, var(--surface)) 100%);
        padding: 1.4rem 1.65rem;
    }
    .dpa-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 600;
        border: 1px solid color-mix(in srgb, var(--accent) 28%, var(--line));
        background: var(--surface);
        color: var(--ink-2);
    }
    .dpa-dot { width: 7px; height: 7px; border-radius: 9999px; background: var(--ok); display: inline-block; }

    .dpa-kpi-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    }
    .dpa-kpi {
        border-radius: 12px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: 1.15rem 1.25rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-width: 0;
        transition: transform 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease;
    }
    .dpa-kpi:hover { transform: translateY(-2px); border-color: var(--line-strong); box-shadow: 0 8px 22px -12px rgba(0,0,0,0.18); }
    .dpa-kpi-clickable { cursor: pointer; text-align: left; }
    .dpa-kpi-icon {
        width: 34px; height: 34px; border-radius: 9px;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .dpa-mini-bar { height: 5px; width: 100%; border-radius: 9999px; background: var(--bg); overflow: hidden; margin-top: 0.65rem; }
    .dpa-mini-fill { height: 100%; border-radius: 9999px; transition: width 0.35s ease; }

    .dpa-analytics-grid {
        display: grid;
        gap: 1.25rem;
        grid-template-columns: minmax(280px, 1fr) minmax(320px, 1.45fr);
    }
    .dpa-panel {
        border-radius: 12px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: 1.3rem 1.4rem;
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }
    .dpa-donut-wrap {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        margin-top: 1.15rem;
    }
    .dpa-donut-seg { cursor: pointer; transition: stroke-width 0.16s ease, opacity 0.16s ease; }
    .dpa-donut-seg:hover { stroke-width: 21; }
    .dpa-legend-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.44rem 0.6rem;
        border-radius: 8px;
        border: 1px solid transparent;
        background: transparent;
        cursor: pointer;
        text-align: left;
        font-size: 12px;
        color: var(--ink);
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .dpa-legend-btn:hover, .dpa-legend-btn.is-active { background: var(--bg); border-color: var(--line); }

    .dpa-tabs {
        display: inline-flex;
        gap: 0.25rem;
        padding: 0.25rem;
        border-radius: 9px;
        background: var(--bg);
        border: 1px solid var(--line);
        flex-wrap: wrap;
    }
    .dpa-tab {
        padding: 0.34rem 0.72rem;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 500;
        color: var(--ink-2);
        background: transparent;
        border: none;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    }
    .dpa-tab.is-active {
        background: var(--surface);
        color: var(--ink);
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    .dpa-bar-row {
        padding: 0.55rem 0.65rem;
        border-radius: 9px;
        cursor: pointer;
        border: 1px solid transparent;
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .dpa-bar-row:hover, .dpa-bar-row.is-active { background: var(--bg); border-color: var(--line); }
    .dpa-bar-track {
        height: 10px;
        width: 100%;
        border-radius: 9999px;
        overflow: hidden;
        background: var(--bg);
        border: 1px solid var(--line);
    }
    .dpa-bar-fill { display: flex; height: 100%; border-radius: 9999px; overflow: hidden; }

    .dpa-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--line);
    }
    .dpa-chip-row { display: flex; flex-wrap: wrap; gap: 0.4rem; }
    .dpa-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.32rem 0.72rem;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 500;
        border: 1px solid var(--line);
        background: var(--bg);
        color: var(--ink-2);
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .dpa-chip:hover { border-color: var(--line-strong); color: var(--ink); }
    .dpa-chip.is-active {
        background: var(--accent);
        border-color: var(--accent);
        color: #ffffff;
    }
    .dpa-search {
        padding: 0.42rem 0.75rem;
        border-radius: 8px;
        border: 1px solid var(--line);
        background: var(--bg);
        color: var(--ink);
        font-size: 13px;
        min-width: 210px;
    }
    .dpa-search:focus { outline: none; border-color: var(--accent); }
    .dpa-select {
        padding: 0.42rem 0.7rem;
        border-radius: 8px;
        border: 1px solid var(--line);
        background: var(--bg);
        color: var(--ink);
        font-size: 12px;
    }
    .dpa-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.22rem 0.58rem;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 500;
        border: 1px solid var(--line);
        background: var(--bg);
        white-space: nowrap;
    }
    .dpa-priority-tag {
        display: inline-flex;
        align-items: center;
        padding: 0.1rem 0.45rem;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 600;
        background: color-mix(in srgb, var(--bad) 14%, transparent);
        color: var(--bad);
        border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);
        margin-left: 0.35rem;
        vertical-align: middle;
    }

    @media (max-width: 960px) {
        .dpa-analytics-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .dpa-hero { padding: 1.15rem; }
        .dpa-donut-wrap { flex-direction: column; align-items: stretch; }
        .dpa-search { min-width: 100%; }
    }
</style>

{{-- Hero Ringkas DPA --}}
<section class="dpa-hero">
    <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1rem;">
        <div>
            <span class="dpa-pill">
                <span class="dpa-dot"></span>
                Panel Perwalian DPA &middot; {{ $visibleTotal }} mahasiswa bimbingan
            </span>
            <h1 class="rise text-2xl sm:text-3xl" style="margin-top:0.65rem;">Mahasiswa bimbingan</h1>
            <p class="rise mt-1 text-sm text-ink-2" style="--i: 1">
                Pantau sebaran risiko, progres tindak lanjut, dan prioritas mahasiswa yang perlu segera dibimbing.
            </p>
        </div>

        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.6rem;">
            @if ($priorityCount > 0)
                <button type="button" id="dpa-priority-cta" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:0.4rem;">
                    <span>Prioritas Segera Dihubungi</span>
                    <span class="mono" style="background:rgba(255,255,255,0.2);padding:0.05rem 0.45rem;border-radius:9999px;font-size:11px;">{{ $priorityCount }}</span>
                </button>
            @endif
        </div>
    </div>
</section>

{{-- Pemberitahuan Kenaikan Risiko --}}
@if ($notifications->isNotEmpty())
    <section class="mt-5 rounded-xl border border-bad/40 bg-bad/10 p-4" aria-label="Pemberitahuan kenaikan risiko">
        <p class="text-xs font-semibold uppercase tracking-wider text-bad">Kenaikan Risiko ke Tinggi</p>
        <ul class="mt-2 space-y-1.5 text-sm">
            @foreach ($notifications as $notif)
                <li class="flex items-center justify-between gap-3">
                    <span>
                        @if (in_array($notif->id, $unreadNotifIds, true))
                            <strong class="text-bad">[Baru]</strong>
                        @endif
                        {{ $notif->message }}
                    </span>
                    <span class="mono text-xs text-ink-3">{{ $notif->created_at?->translatedFormat('j M, H:i') }}</span>
                </li>
            @endforeach
        </ul>
    </section>
@endif

@if ($visibleTotal === 0)
    <section class="mt-8 rounded-xl border border-dashed border-line-strong bg-surface p-8 text-center">
        <h2 class="text-xl font-medium">Belum ada mahasiswa bimbingan</h2>
        <p class="mt-2 text-sm text-ink-2">Daftar otomatis terisi setelah Sekretariat FTII menetapkan mahasiswa bimbingan untuk Anda.</p>
    </section>
@else
    {{-- 4 KPI Cards Khusus DPA --}}
    <section class="dpa-kpi-grid mt-6" aria-label="Ringkasan utama DPA">
        <div class="dpa-kpi">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;">
                <div>
                    <p class="text-xs text-ink-3">Anak Bimbingan Aktif</p>
                    <p class="num mono mt-1 text-2xl font-semibold">
                        {{ $visibleTotal }} <span class="text-sm font-normal text-ink-3">/ {{ $totalAll }}</span>
                    </p>
                </div>
                <span class="dpa-kpi-icon" style="background:color-mix(in srgb, var(--accent) 14%, transparent);color:var(--accent);">
                    <x-icon name="users" />
                </span>
            </div>
            <div>
                <div class="dpa-mini-bar">
                    <div class="dpa-mini-fill" style="width:{{ $consentPct }}%;background:var(--accent);"></div>
                </div>
                <p class="mt-2 text-xs text-ink-3">Seluruh mahasiswa bimbingan Anda</p>
            </div>
        </div>

        <div class="dpa-kpi">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;">
                <div>
                    <p class="text-xs text-ink-3">Perlu Perhatian</p>
                    <p class="num mono mt-1 text-2xl font-semibold" style="color:{{ $attentionCount > 0 ? 'var(--bad)' : 'var(--ok)' }};">
                        {{ $attentionCount }}
                    </p>
                </div>
                <span class="dpa-kpi-icon" style="background:color-mix(in srgb, var(--bad) 14%, transparent);color:var(--bad);">
                    <x-icon name="warning-circle" />
                </span>
            </div>
            <p class="mt-3 text-xs text-ink-3">
                <strong class="text-ink">{{ $counts['tinggi'] }}</strong> tinggi &middot;
                <strong class="text-ink">{{ $counts['sedang'] }}</strong> sedang
                @if ($escalatedCount > 0)
                    &middot; <span style="color:var(--bad);font-weight:600;">&uarr; {{ $escalatedCount }} naik</span>
                @endif
            </p>
        </div>

        <button type="button" class="dpa-kpi dpa-kpi-clickable" data-quick-priority>
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;width:100%;">
                <div>
                    <p class="text-xs text-ink-3">Tugas Prioritas DPA</p>
                    <p class="num mono mt-1 text-2xl font-semibold" style="color:{{ $priorityCount > 0 ? 'var(--warn)' : 'var(--ok)' }};">
                        {{ $priorityCount }}
                    </p>
                </div>
                <span class="dpa-kpi-icon" style="background:color-mix(in srgb, var(--warn) 16%, transparent);color:var(--warn);">
                    <x-icon name="list-checks" />
                </span>
            </div>
            <p class="mt-3 text-xs text-ink-3">
                @if ($priorityCount > 0)
                    Risiko tinggi/sedang &amp; belum dihubungi &rarr; <span style="color:var(--accent);">Saring</span>
                @else
                    Semua mahasiswa berisiko sudah ditindaklanjuti
                @endif
            </p>
        </button>

        <div class="dpa-kpi">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;">
                <div>
                    <p class="text-xs text-ink-3">Progres Perwalian</p>
                    <p class="num mono mt-1 text-2xl font-semibold" style="color:var(--ok);">
                        {{ $handledPct }}%
                    </p>
                </div>
                <span class="dpa-kpi-icon" style="background:color-mix(in srgb, var(--ok) 14%, transparent);color:var(--ok);">
                    <x-icon name="check-circle" />
                </span>
            </div>
            <div>
                <div class="dpa-mini-bar">
                    <div class="dpa-mini-fill" style="width:{{ $handledPct }}%;background:var(--ok);"></div>
                </div>
                <p class="mt-2 text-xs text-ink-3">{{ $handledCount }} dari {{ $visibleTotal }} sudah bimbingan / selesai</p>
            </div>
        </div>
    </section>

    {{-- Visualisasi Grafik Khusus DPA --}}
    <section class="dpa-analytics-grid mt-6" aria-label="Visualisasi data anak bimbingan">
        {{-- Kiri: Donut Distribusi Risiko --}}
        <div class="dpa-panel">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;">
                <div>
                    <h2 class="text-base font-semibold">Distribusi Risiko Bimbingan</h2>
                    <p class="mt-0.5 text-xs text-ink-3">Klik segmen atau kategori untuk menyaring daftar mahasiswa.</p>
                </div>
                <span class="mono text-xs text-ink-3">{{ $attentionPct }}% atensi</span>
            </div>

            <div class="dpa-donut-wrap">
                <svg viewBox="0 0 148 148" width="142" height="142" role="img" aria-label="Diagram donat risiko mahasiswa bimbingan" style="flex-shrink:0;margin:0 auto;">
                    <circle cx="74" cy="74" r="{{ $donutRadius }}" fill="none" stroke="var(--bg)" stroke-width="18" />
                    @php $offset = 0; @endphp
                    @foreach ($donutSegments as $seg)
                        @if ($seg['count'] > 0 && $visibleTotal > 0)
                            @php
                                $dash = ($seg['count'] / $visibleTotal) * $donutCircumference;
                            @endphp
                            <circle
                                class="dpa-donut-seg"
                                data-filter-risk="{{ $seg['key'] }}"
                                cx="74" cy="74" r="{{ $donutRadius }}"
                                fill="none"
                                stroke="{{ $seg['color'] }}"
                                stroke-width="18"
                                stroke-dasharray="{{ number_format($dash, 2, '.', '') }} {{ number_format($donutCircumference, 2, '.', '') }}"
                                stroke-dashoffset="-{{ number_format($offset, 2, '.', '') }}"
                                transform="rotate(-90 74 74)"
                            >
                                <title>{{ $seg['label'] }}: {{ $seg['count'] }} mahasiswa ({{ $seg['pct'] }}%)</title>
                            </circle>
                            @php $offset += $dash; @endphp
                        @endif
                    @endforeach
                    <text x="74" y="70" text-anchor="middle" fill="var(--ink)" font-size="24" font-weight="700" font-family="ui-monospace, SFMono-Regular, monospace">{{ $visibleTotal }}</text>
                    <text x="74" y="88" text-anchor="middle" fill="var(--ink-3)" font-size="11">bimbingan</text>
                </svg>

                <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:0.25rem;">
                    @foreach ($donutSegments as $seg)
                        <button type="button" class="dpa-legend-btn" data-filter-risk="{{ $seg['key'] }}">
                            <span style="display:inline-flex;align-items:center;gap:0.5rem;min-width:0;">
                                <span style="width:9px;height:9px;border-radius:9999px;background:{{ $seg['color'] }};flex-shrink:0;"></span>
                                <span class="truncate">{{ $seg['label'] }}</span>
                            </span>
                            <span class="mono" style="flex-shrink:0;color:var(--ink-2);">
                                <strong style="color:var(--ink);">{{ $seg['count'] }}</strong> ({{ $seg['pct'] }}%)
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Kanan: Panel Tab Tugas DPA / Semester / Faktor Dominan --}}
        <div class="dpa-panel">
            <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.75rem;">
                <div>
                    <h2 class="text-base font-semibold">Pantauan Tugas &amp; Sebaran</h2>
                    <p class="mt-0.5 text-xs text-ink-3">Klik baris grafik untuk langsung menyaring tabel di bawah.</p>
                </div>
                <div class="dpa-tabs" role="tablist" aria-label="Mode grafik DPA">
                    <button type="button" class="dpa-tab is-active" data-dpa-tab="status" role="tab" aria-selected="true">Tindak Lanjut</button>
                    <button type="button" class="dpa-tab" data-dpa-tab="semester" role="tab" aria-selected="false">Per Semester</button>
                    <button type="button" class="dpa-tab" data-dpa-tab="faktor" role="tab" aria-selected="false">Faktor Dominan</button>
                </div>
            </div>

            {{-- Tab 1: Progres Tindak Lanjut Perwalian --}}
            <div class="mt-4 space-y-2" data-dpa-panel="status">
                @foreach ($followUpMeta as $stKey => $meta)
                    @php
                        $cnt = (int) ($statusCounts[$stKey] ?? 0);
                        $pct = $visibleTotal > 0 ? round(($cnt / $visibleTotal) * 100) : 0;
                    @endphp
                    <div class="dpa-bar-row" data-filter-status="{{ $stKey }}" role="button" tabindex="0">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;margin-bottom:0.35rem;">
                            <div style="min-width:0;">
                                <span class="text-sm font-medium">{{ $meta['label'] }}</span>
                                <span class="ml-2 text-xs text-ink-3 hidden sm:inline">{{ $meta['desc'] }}</span>
                            </div>
                            <span class="mono text-xs" style="flex-shrink:0;">
                                <strong class="text-ink">{{ $cnt }}</strong>
                                <span class="text-ink-3">({{ $pct }}%)</span>
                            </span>
                        </div>
                        <div class="dpa-bar-track">
                            <div class="dpa-bar-fill" style="width:{{ $pct }}%;background:{{ $meta['color'] }};"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Tab 2: Sebaran Risiko per Semester --}}
            <div class="mt-4 space-y-2" data-dpa-panel="semester" style="display:none;">
                @forelse ($semesterStats as $sem)
                    @php
                        $barW = round(($sem['total'] / $maxSemTotal) * 100);
                        $wT = $sem['total'] > 0 ? ($sem['tinggi'] / $sem['total']) * 100 : 0;
                        $wS = $sem['total'] > 0 ? ($sem['sedang'] / $sem['total']) * 100 : 0;
                        $wR = $sem['total'] > 0 ? ($sem['rendah'] / $sem['total']) * 100 : 0;
                        $wB = $sem['total'] > 0 ? ($sem['belum'] / $sem['total']) * 100 : 0;
                    @endphp
                    <div class="dpa-bar-row" data-filter-sem="{{ $sem['key'] }}" role="button" tabindex="0">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;margin-bottom:0.35rem;">
                            <span class="text-sm font-medium">{{ $sem['label'] }}</span>
                            <span class="mono text-xs text-ink-3">
                                <strong class="text-ink">{{ $sem['total'] }} mhs</strong>
                                @if ($sem['tinggi'] > 0) &middot; <span style="color:var(--bad);">{{ $sem['tinggi'] }} T</span> @endif
                                @if ($sem['sedang'] > 0) &middot; <span style="color:var(--warn);">{{ $sem['sedang'] }} S</span> @endif
                                @if ($sem['rendah'] > 0) &middot; <span style="color:var(--ok);">{{ $sem['rendah'] }} R</span> @endif
                            </span>
                        </div>
                        <div class="dpa-bar-track">
                            <div class="dpa-bar-fill" style="width:{{ $barW }}%;">
                                @if ($wT > 0)<span style="width:{{ $wT }}%;background:var(--bad);"></span>@endif
                                @if ($wS > 0)<span style="width:{{ $wS }}%;background:var(--warn);"></span>@endif
                                @if ($wR > 0)<span style="width:{{ $wR }}%;background:var(--ok);"></span>@endif
                                @if ($wB > 0)<span style="width:{{ $wB }}%;background:var(--line-strong);"></span>@endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-ink-3">Belum ada data semester untuk ditampilkan.</p>
                @endforelse
            </div>

            {{-- Tab 3: Faktor Risiko Dominan --}}
            <div class="mt-4 space-y-2" data-dpa-panel="faktor" style="display:none;">
                @forelse ($topFactors as $fac)
                    @php $facW = round(($fac['count'] / $maxFactorCount) * 100); @endphp
                    <div class="dpa-bar-row" style="cursor:default;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;margin-bottom:0.35rem;">
                            <span class="text-sm font-medium truncate">{{ $fac['label'] }}</span>
                            <span class="mono text-xs text-ink-2" style="flex-shrink:0;">
                                <strong class="text-ink">{{ $fac['count'] }}</strong> mahasiswa
                            </span>
                        </div>
                        <div class="dpa-bar-track">
                            <div class="dpa-bar-fill" style="width:{{ $facW }}%;background:var(--accent);"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-ink-3">Tidak ada faktor risiko dominan saat ini—seluruh mahasiswa bimbingan dalam kondisi aman.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Daftar Mahasiswa Bimbingan --}}
    <section class="dpa-panel mt-6" id="dpa-table-section" aria-label="Daftar mahasiswa bimbingan">
        <div class="dpa-toolbar">
            <div>
                <h2 class="text-base font-semibold">Daftar Anak Bimbingan</h2>
                <p class="mt-0.5 text-xs text-ink-3">
                    Menampilkan <strong id="dpa-visible-count" class="text-ink">{{ $rows->count() }}</strong> dari {{ $rows->count() }} mahasiswa
                    <span id="dpa-active-filter-label" class="ml-1" style="color:var(--accent);"></span>
                </p>
            </div>
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem;">
                <input type="search" id="dpa-search-input" class="dpa-search" placeholder="Cari nama atau NIM..." aria-label="Cari nama atau NIM mahasiswa">
                <select id="dpa-status-select" class="dpa-select" aria-label="Saring status tindak lanjut">
                    <option value="">Semua status tindak lanjut</option>
                    @foreach ($followUpMeta as $stKey => $meta)
                        <option value="{{ $stKey }}" @selected($statusFilter === $stKey)>{{ $meta['label'] }} ({{ $statusCounts[$stKey] ?? 0 }})</option>
                    @endforeach
                </select>
                <button type="button" id="dpa-reset-filters" class="btn btn-quiet btn-sm" style="display:none;">Reset filter</button>
            </div>
        </div>

        <div class="dpa-chip-row mt-4" role="group" aria-label="Saring menurut tingkat risiko">
            <button type="button" class="dpa-chip {{ $filter === null ? 'is-active' : '' }}" data-chip-risk="">
                Semua <span class="mono opacity-80">{{ $counts['semua'] }}</span>
            </button>
            @foreach (['tinggi' => 'Tinggi', 'sedang' => 'Sedang', 'rendah' => 'Rendah', 'belum' => 'Belum dihitung'] as $rKey => $rLabel)
                <button type="button" class="dpa-chip {{ $filter === $rKey ? 'is-active' : '' }}" data-chip-risk="{{ $rKey }}">
                    {{ $rLabel }} <span class="mono opacity-80">{{ $counts[$rKey] ?? 0 }}</span>
                </button>
            @endforeach
        </div>

        @if ($rows->isEmpty())
            <p class="mt-6 text-sm text-ink-2">Tidak ada mahasiswa pada filter ini. <a href="{{ route('dpa.bimbingan') }}" class="underline">Tampilkan semua</a>.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="tbl min-w-[52rem]" id="dpa-student-table">
                    <thead>
                        <tr>
                            <th>Mahasiswa</th>
                            <th>Risiko &amp; Skor</th>
                            <th>Tren</th>
                            <th>Tindak Lanjut</th>
                            <th>Sem</th>
                            <th>Faktor Utama</th>
                            <th><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php
                                $res = $row['result'];
                                $stKey = $row['follow_up_status'] ?? 'belum_dihubungi';
                                $stColor = $followUpMeta[$stKey]['color'] ?? 'var(--ink-3)';
                                $semKey = $res?->semester ? (string) ((int) $res->semester) : '0';
                            @endphp
                            <tr
                                data-row-search="{{ mb_strtolower($row['student']->name . ' ' . $row['student']->username) }}"
                                data-row-risk="{{ $row['level'] }}"
                                data-row-status="{{ $stKey }}"
                                data-row-sem="{{ $semKey }}"
                                data-row-priority="{{ ! empty($row['is_priority']) ? '1' : '0' }}"
                            >
                                <td>
                                    <p class="font-medium">
                                        {{ $row['student']->name }}
                                        @if (! empty($row['is_priority']))
                                            <span class="dpa-priority-tag">Prioritas</span>
                                        @endif
                                    </p>
                                    <p class="mono text-xs text-ink-3">{{ $row['student']->username }}</p>
                                </td>
                                <td>
                                    <div style="display:inline-flex;align-items:center;gap:0.45rem;">
                                        <x-risk :level="$row['level']" />
                                        @if ($res)
                                            <span class="mono text-xs text-ink-3">{{ number_format((float) $res->score, 2) }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-xs">
                                    @if (! empty($row['escalated']))
                                        <span class="rounded border border-bad/40 bg-bad/10 px-2 py-0.5 font-medium text-bad">&uarr; Naik</span>
                                    @elseif (! empty($row['previous_level']))
                                        <span class="text-ink-3">Stabil</span>
                                    @else
                                        <span class="text-ink-3">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="dpa-status-badge">
                                        <span style="width:7px;height:7px;border-radius:9999px;background:{{ $stColor }};"></span>
                                        {{ \App\Models\StudentSetting::followUpLabelFor($stKey) }}
                                    </span>
                                </td>
                                <td class="num">{{ $res?->semester ?? '-' }}</td>
                                <td class="text-sm text-ink-2">{{ $res && count($res->factors) ? $res->factors[0]['label'] : '-' }}</td>
                                <td class="text-right">
                                    @if ($res)
                                        <a href="{{ route('dpa.bimbingan.show', $row['student']) }}" class="btn btn-quiet btn-sm">Detail &rarr;</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p id="dpa-empty-filtered" class="mt-6 text-center text-sm text-ink-3" style="display:none;">
                Tidak ada mahasiswa yang cocok dengan filter saat ini.
            </p>
        @endif
    </section>

    <script>
        (function () {
            const state = {
                risk: @json($filter ?? ''),
                status: @json($statusFilter ?? ''),
                sem: '',
                priorityOnly: false,
                q: ''
            };

            const rows = Array.from(document.querySelectorAll('#dpa-student-table tbody tr'));
            const countEl = document.getElementById('dpa-visible-count');
            const filterLabelEl = document.getElementById('dpa-active-filter-label');
            const emptyEl = document.getElementById('dpa-empty-filtered');
            const resetBtn = document.getElementById('dpa-reset-filters');
            const searchInput = document.getElementById('dpa-search-input');
            const statusSelect = document.getElementById('dpa-status-select');

            function applyFilters(scrollToTable) {
                let shown = 0;
                const q = state.q.trim().toLowerCase();

                rows.forEach(function (tr) {
                    const okRisk = !state.risk || tr.dataset.rowRisk === state.risk;
                    const okStatus = !state.status || tr.dataset.rowStatus === state.status;
                    const okSem = !state.sem || tr.dataset.rowSem === state.sem;
                    const okPrio = !state.priorityOnly || tr.dataset.rowPriority === '1';
                    const okQ = !q || (tr.dataset.rowSearch || '').includes(q);
                    const visible = okRisk && okStatus && okSem && okPrio && okQ;
                    tr.style.display = visible ? '' : 'none';
                    if (visible) shown++;
                });

                if (countEl) countEl.textContent = String(shown);
                if (emptyEl) emptyEl.style.display = shown === 0 ? '' : 'none';

                const activeTags = [];
                if (state.priorityOnly) activeTags.push('Prioritas Segera Dihubungi');
                if (state.risk) activeTags.push('Risiko: ' + state.risk);
                if (state.status) activeTags.push('Status: ' + state.status.replace('_', ' '));
                if (state.sem) activeTags.push(state.sem === '0' ? 'Belum dihitung' : 'Semester ' + state.sem);

                if (filterLabelEl) {
                    filterLabelEl.textContent = activeTags.length ? '· Filter: ' + activeTags.join(', ') : '';
                }
                if (resetBtn) {
                    resetBtn.style.display = (activeTags.length || q) ? '' : 'none';
                }

                document.querySelectorAll('[data-chip-risk]').forEach(function (btn) {
                    btn.classList.toggle('is-active', !state.priorityOnly && (btn.dataset.chipRisk || '') === state.risk);
                });
                document.querySelectorAll('.dpa-legend-btn').forEach(function (btn) {
                    btn.classList.toggle('is-active', (btn.dataset.filterRisk || '') === state.risk && state.risk !== '');
                });
                document.querySelectorAll('[data-filter-status]').forEach(function (el) {
                    el.classList.toggle('is-active', (el.dataset.filterStatus || '') === state.status && state.status !== '');
                });
                document.querySelectorAll('[data-filter-sem]').forEach(function (el) {
                    el.classList.toggle('is-active', (el.dataset.filterSem || '') === state.sem && state.sem !== '');
                });
                if (statusSelect && statusSelect.value !== state.status) {
                    statusSelect.value = state.status;
                }

                if (scrollToTable) {
                    const target = document.getElementById('dpa-table-section');
                    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            // Tab switching
            document.querySelectorAll('[data-dpa-tab]').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const key = tab.dataset.dpaTab;
                    document.querySelectorAll('[data-dpa-tab]').forEach(function (t) {
                        const active = t.dataset.dpaTab === key;
                        t.classList.toggle('is-active', active);
                        t.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    document.querySelectorAll('[data-dpa-panel]').forEach(function (p) {
                        p.style.display = p.dataset.dpaPanel === key ? '' : 'none';
                    });
                });
            });

            // Donut & legend click
            document.querySelectorAll('[data-filter-risk]').forEach(function (el) {
                el.addEventListener('click', function () {
                    const key = el.dataset.filterRisk || '';
                    state.priorityOnly = false;
                    state.risk = state.risk === key ? '' : key;
                    applyFilters(true);
                });
            });

            // Risk chips
            document.querySelectorAll('[data-chip-risk]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    state.priorityOnly = false;
                    state.risk = btn.dataset.chipRisk || '';
                    applyFilters(false);
                });
            });

            // Status bars
            document.querySelectorAll('[data-filter-status]').forEach(function (el) {
                el.addEventListener('click', function () {
                    const key = el.dataset.filterStatus || '';
                    state.priorityOnly = false;
                    state.status = state.status === key ? '' : key;
                    applyFilters(true);
                });
            });

            // Semester bars
            document.querySelectorAll('[data-filter-sem]').forEach(function (el) {
                el.addEventListener('click', function () {
                    const key = el.dataset.filterSem || '';
                    state.priorityOnly = false;
                    state.sem = state.sem === key ? '' : key;
                    applyFilters(true);
                });
            });

            // Priority quick button & KPI card
            [document.getElementById('dpa-priority-cta'), document.querySelector('[data-quick-priority]')].forEach(function (el) {
                if (!el) return;
                el.addEventListener('click', function () {
                    state.risk = '';
                    state.status = '';
                    state.sem = '';
                    state.priorityOnly = !state.priorityOnly;
                    applyFilters(true);
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    state.q = searchInput.value;
                    applyFilters(false);
                });
            }
            if (statusSelect) {
                statusSelect.addEventListener('change', function () {
                    state.priorityOnly = false;
                    state.status = statusSelect.value;
                    applyFilters(false);
                });
            }
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    state.risk = '';
                    state.status = '';
                    state.sem = '';
                    state.priorityOnly = false;
                    state.q = '';
                    if (searchInput) searchInput.value = '';
                    applyFilters(false);
                });
            }

            applyFilters(false);
        })();
    </script>
@endif
@endsection