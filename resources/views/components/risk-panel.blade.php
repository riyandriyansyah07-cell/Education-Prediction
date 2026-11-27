{{-- Panel hasil risiko. Dipakai di ringkasan mahasiswa, detail DPA, dan pratinjau beranda. --}}
@props(['result', 'thresholds' => ['sedang' => 2.5, 'tinggi' => 5.0], 'audience' => 'student', 'version' => null, 'compact' => false])
@php
    $level = $result->level;
    $score = (float) $result->score;
    $factors = $result->factors ?? [];
    $explanationUnavailable = ! empty($result->features['explanation_unavailable']);
    if ($compact) {
        $factors = array_slice($factors, 0, 3);
    }
    $suggestions = collect($result->suggestions ?? []);
    if ($audience === 'dpa') {
        $suggestions = $suggestions->reject(fn ($s) => str_contains($s, 'DPA'));
    }
    $copy = [
        'rendah' => 'Pola kehadiran dan nilai berada di jalur aman.',
        'sedang' => 'Ada beberapa faktor yang perlu diperhatikan lebih awal.',
        'tinggi' => 'Perlu pendampingan dan langkah perbaikan lebih awal.',
    ];
    $pos = fn (float $v) => max(0, min(100, $v / \App\Support\RiskModel::MAX_SCORE * 100));
    $maxPoints = 3.0;
    $trustLabel = method_exists($result, 'trustLabel') ? $result->trustLabel() : 'Sistem akademik';
    $completeness = method_exists($result, 'completenessSummary') ? $result->completenessSummary() : '4/4 komponen';
@endphp

<style>
    .rp-status-box {
        border: 1px solid var(--line);
        border-radius: 16px;
        background: var(--surface);
        padding: 1.25rem 1.35rem;
    }
    .rp-meta-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem 0.55rem;
        font-size: 0.75rem;
        color: var(--ink-3);
    }
    .rp-meta-sep {
        opacity: 0.55;
    }
    .rp-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.16rem 0.6rem;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 600;
        border: 1px solid var(--line);
        background: var(--sunken);
        color: var(--ink-2);
    }
    .rp-section {
        margin-top: 1.5rem;
        padding-top: 1.35rem;
        border-top: 1px solid var(--line);
    }
    .rp-gauge-track {
        position: relative;
        height: 8px;
        border-radius: 9999px;
        overflow: hidden;
        background: var(--sunken);
    }
    .rp-factor-track {
        margin-top: 0.45rem;
        height: 5px;
        width: 100%;
        border-radius: 9999px;
        background: color-mix(in srgb, var(--accent) 10%, var(--sunken));
        overflow: hidden;
    }
    .rp-step-num {
        width: 24px;
        height: 24px;
        border-radius: 9999px;
        background: var(--accent-soft);
        color: var(--accent);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
        margin-top: 1px;
    }
    @media (max-width: 640px) {
        .rp-status-box { padding: 1rem 1.05rem; }
    }
</style>

<article class="risk-{{ $level }}">
    <div class="rp-status-box">
        <div class="rp-meta-row">
            <span class="mono">Semester {{ $result->semester }}</span>
            <span class="rp-meta-sep">&middot;</span>
            <span>{{ $trustLabel }}</span>
            <span class="rp-meta-sep">&middot;</span>
            <span>{{ $completeness }}</span>
            @if ($result->is_projection)
                <span class="rp-meta-sep">&middot;</span>
                <span style="color:var(--accent);font-weight:600;">Proyeksi</span>
            @endif
        </div>

        <div style="margin-top:0.65rem;display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:0.5rem 1.5rem;">
            <h2 class="risk" style="font-size:clamp(1.65rem, 3.2vw, 2.35rem);font-weight:700;line-height:1.15;margin:0;letter-spacing:-0.02em;">
                {{ ['rendah' => 'Risiko rendah', 'sedang' => 'Risiko sedang', 'tinggi' => 'Risiko tinggi'][$level] }}
            </h2>
            <p class="num text-ink" style="margin:0;">
                <span class="mono" style="font-size:clamp(1.6rem, 2.6vw, 2.1rem);font-weight:700;letter-spacing:-0.02em;">{{ \App\Support\Fmt::num($score, 1) }}</span>
                <span class="text-sm text-ink-3"> / 10</span>
            </p>
        </div>

        <p class="max-w-[66ch] text-sm text-ink-2" style="margin-top:0.65rem;line-height:1.6;">
            {{ $audience === 'student' ? ($result->features['encouragement'] ?? $copy[$level]) : $copy[$level] }}
        </p>

        @if ($result->projection_gap_flagged)
            <p class="mt-3 rounded-lg border border-warn/40 bg-warn/10 px-3.5 py-2.5 text-xs text-ink">
                Selisih cukup besar antara proyeksi mandiri dan nilai akhir KHS resmi.
            </p>
        @endif

        <div class="mt-5" role="img" aria-label="Skor risiko {{ \App\Support\Fmt::num($score, 1) }} dari 10.">
            <div class="relative pb-6">
                <div class="rp-gauge-track">
                    <div class="absolute inset-y-0 left-0" style="width: {{ $pos($thresholds['sedang']) }}%; background: #10b981;"></div>
                    <div class="absolute inset-y-0" style="left: {{ $pos($thresholds['sedang']) }}%; width: {{ $pos($thresholds['tinggi']) - $pos($thresholds['sedang']) }}%; background: #f59e0b;"></div>
                    <div class="absolute inset-y-0 right-0" style="left: {{ $pos($thresholds['tinggi']) }}%; background: #f43f5e;"></div>
                </div>
                <span class="absolute -top-1 h-4 w-1 -translate-x-1/2 bg-ink" style="left: {{ $pos($score) }}%; border-radius:999px; box-shadow: 0 0 0 2px var(--surface);"></span>
                <div class="num mono absolute inset-x-0 bottom-0 h-4 text-xs text-ink-3">
                    <span class="absolute left-0">0</span>
                    <span class="absolute -translate-x-1/2" style="left: {{ $pos($thresholds['sedang']) }}%">{{ \App\Support\Fmt::num($thresholds['sedang'], 1) }}</span>
                    <span class="absolute -translate-x-1/2" style="left: {{ $pos($thresholds['tinggi']) }}%">{{ \App\Support\Fmt::num($thresholds['tinggi'], 1) }}</span>
                    <span class="absolute right-0">10</span>
                </div>
            </div>
        </div>
    </div>

    <section class="rp-section" aria-labelledby="faktor-{{ $audience }}">
        <div style="display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:0.5rem;">
            <h3 id="faktor-{{ $audience }}" class="text-base font-semibold text-ink">{{ $audience === 'dpa' ? 'Faktor utama' : 'Penyebab utama' }}</h3>
            <a href="{{ route('metodologi') }}" class="text-xs font-medium text-accent hover:underline">Metodologi &rarr;</a>
        </div>
        @if ($explanationUnavailable)
            <p class="text-sm text-ink-2" style="margin-top:0.75rem;">Penjelasan belum tersedia. Klik Hitung ulang.</p>
        @elseif (count($factors))
            <ul style="list-style:none;margin:1rem 0 0;padding:0;display:flex;flex-direction:column;gap:1.05rem;">
                @foreach ($factors as $f)
                    <li class="rise" style="--i: {{ $loop->index }}">
                        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:0.75rem;">
                            <p class="text-sm font-semibold text-ink" style="margin:0;">{{ $f['label'] }}</p>
                            <p class="num mono shrink-0 text-xs font-semibold text-accent" style="margin:0;">+{{ \App\Support\Fmt::num($f['points'], 2) }}</p>
                        </div>
                        @if (! empty($f['detail']))
                            <p class="mt-1 text-xs text-ink-2" style="line-height:1.55;">{{ $f['detail'] }}</p>
                        @endif
                        <div class="rp-factor-track" aria-hidden="true">
                            <div class="grow h-full rounded-full bg-accent" style="--i: {{ $loop->index }}; width: {{ min(100, $f['points'] / $maxPoints * 100) }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-ink-2" style="margin-top:0.75rem;display:inline-flex;align-items:center;gap:0.45rem;padding:0.5rem 0.85rem;border-radius:10px;background:color-mix(in srgb, var(--ok) 10%, var(--sunken));border:1px solid color-mix(in srgb, var(--ok) 28%, var(--line));color:var(--ok);font-size:0.82rem;">
                Tidak ada faktor risiko yang menonjol.
            </p>
        @endif
    </section>

    @unless ($compact)
    <section class="rp-section" aria-labelledby="saran-{{ $audience }}">
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.5rem;">
            <h3 id="saran-{{ $audience }}" class="text-base font-semibold text-ink">{{ $audience === 'dpa' ? 'Bahan pendampingan' : 'Langkah yang bisa dicoba' }}</h3>
            @if ($result->llm_generated)
                <span class="rp-badge" style="color:var(--accent);border-color:color-mix(in srgb, var(--accent) 32%, var(--line));background:var(--accent-soft);">AI (LLM)</span>
            @endif
        </div>
        <ol style="list-style:none;margin:1rem 0 0;padding:0;display:flex;flex-direction:column;gap:0.9rem;" class="text-sm">
            @foreach ($suggestions as $s)
                <li style="display:flex;align-items:flex-start;gap:0.75rem;line-height:1.6;">
                    <span class="rp-step-num mono">{{ $loop->iteration }}</span>
                    <span class="text-ink-2">{{ $s }}</span>
                </li>
            @endforeach
        </ol>
    </section>
    @endunless

    <p class="mt-6 border-t border-line pt-3 text-xs text-ink-3">
        Perkiraan berbasis pola data, bukan keputusan akademik &middot; {{ $result->data_as_of?->translatedFormat('j M Y') }}@if ($version) &middot; {{ $version }}@endif
    </p>
</article>