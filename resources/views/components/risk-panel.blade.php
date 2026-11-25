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
    .rp-hero {
        border-radius: 14px;
        border: 1px solid var(--line);
        border-left: 4px solid var(--c);
        background: var(--surface);
        padding: 1.45rem 1.6rem;
        box-shadow: var(--shadow-card);
    }
    .rp-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.18rem 0.58rem;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 500;
        border: 1px solid var(--line);
        background: var(--bg);
        color: var(--ink-2);
    }
    .rp-grid {
        display: grid;
        gap: 1.25rem;
        margin-top: 1.25rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: start;
    }
    .rp-card {
        border-radius: 14px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: 1.35rem 1.5rem;
        box-shadow: var(--shadow-card);
    }
    @media (max-width: 820px) {
        .rp-grid { grid-template-columns: 1fr; }
        .rp-hero, .rp-card { padding: 1.15rem 1.2rem; }
    }
</style>

<article class="risk-{{ $level }}">
    <div class="rp-hero">
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.45rem;">
            <span class="rp-badge mono">Semester {{ $result->semester }}</span>
            <span class="rp-badge">{{ $trustLabel }}</span>
            <span class="rp-badge">{{ $completeness }}</span>
            @if ($result->is_projection)
                <span class="rp-badge" style="color:var(--accent);border-color:color-mix(in srgb, var(--accent) 40%, var(--line));background:color-mix(in srgb, var(--accent) 10%, var(--surface));">Proyeksi</span>
            @endif
        </div>

        <div style="margin-top:0.85rem;display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:0.5rem 1.5rem;">
            <h2 class="risk" style="font-size:clamp(1.75rem, 3.4vw, 2.6rem);line-height:1.15;margin:0;">
                {{ ['rendah' => 'Risiko rendah', 'sedang' => 'Risiko sedang', 'tinggi' => 'Risiko tinggi'][$level] }}
            </h2>
            <p class="num text-ink" style="margin:0;">
                <span class="mono" style="font-size:clamp(1.55rem, 2.5vw, 2rem);font-weight:650;">{{ \App\Support\Fmt::num($score, 1) }}</span>
                <span class="text-sm text-ink-3"> / 10</span>
            </p>
        </div>

        <p class="mt-3 max-w-[64ch] text-sm text-ink-2" style="line-height:1.6;">
            {{ $audience === 'student' ? ($result->features['encouragement'] ?? $copy[$level]) : $copy[$level] }}
        </p>

        @if ($result->projection_gap_flagged)
            <p class="mt-3 rounded-lg border border-warn/40 bg-warn/10 px-3.5 py-2.5 text-xs text-ink">
                Selisih cukup besar antara proyeksi mandiri dan nilai akhir KHS resmi.
            </p>
        @endif

        <div class="mt-6" role="img" aria-label="Skor risiko {{ \App\Support\Fmt::num($score, 1) }} dari 10.">
            <div class="relative pb-6">
                <div class="relative h-3 overflow-hidden rounded-full" style="border:1px solid var(--line);">
                    <div class="absolute inset-y-0 left-0" style="width: {{ $pos($thresholds['sedang']) }}%; background: color-mix(in oklab, var(--ok) 36%, transparent)"></div>
                    <div class="absolute inset-y-0" style="left: {{ $pos($thresholds['sedang']) }}%; width: {{ $pos($thresholds['tinggi']) - $pos($thresholds['sedang']) }}%; background: color-mix(in oklab, var(--warn) 36%, transparent)"></div>
                    <div class="absolute inset-y-0 right-0" style="left: {{ $pos($thresholds['tinggi']) }}%; background: color-mix(in oklab, var(--bad) 36%, transparent)"></div>
                </div>
                <span class="absolute -top-1 h-5 w-0.5 -translate-x-1/2 bg-ink" style="left: {{ $pos($score) }}%; border-radius:999px;"></span>
                <div class="num absolute inset-x-0 bottom-0 h-4 text-xs text-ink-3">
                    <span class="absolute left-0">0</span>
                    <span class="absolute -translate-x-1/2" style="left: {{ $pos($thresholds['sedang']) }}%">{{ \App\Support\Fmt::num($thresholds['sedang'], 1) }}</span>
                    <span class="absolute -translate-x-1/2" style="left: {{ $pos($thresholds['tinggi']) }}%">{{ \App\Support\Fmt::num($thresholds['tinggi'], 1) }}</span>
                    <span class="absolute right-0">10</span>
                </div>
            </div>
        </div>
    </div>

    <div class="{{ $compact ? 'mt-5' : 'rp-grid' }}">
        <section class="rp-card" aria-labelledby="faktor-{{ $audience }}">
            <div style="display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:0.5rem;">
                <h3 id="faktor-{{ $audience }}" class="text-base font-semibold">{{ $audience === 'dpa' ? 'Faktor utama' : 'Penyebab utama' }}</h3>
                <a href="{{ route('metodologi') }}" class="text-xs font-medium text-accent hover:underline">Metodologi &rarr;</a>
            </div>
            @if ($explanationUnavailable)
                <p class="mt-3 text-sm text-ink-2">Penjelasan belum tersedia. Klik Hitung ulang.</p>
            @elseif (count($factors))
                <ul style="list-style:none;margin:1rem 0 0;padding:0;display:flex;flex-direction:column;gap:0.95rem;">
                    @foreach ($factors as $f)
                        <li class="rise" style="--i: {{ $loop->index }}">
                            <div style="display:flex;align-items:baseline;justify-content:space-between;gap:0.75rem;">
                                <p class="text-sm font-medium text-ink" style="margin:0;">{{ $f['label'] }}</p>
                                <p class="num mono shrink-0 text-xs text-ink-2" style="margin:0;">+{{ \App\Support\Fmt::num($f['points'], 2) }}</p>
                            </div>
                            @if (! empty($f['detail']))
                                <p class="mt-1 text-xs text-ink-2" style="line-height:1.5;">{{ $f['detail'] }}</p>
                            @endif
                            <div style="margin-top:0.45rem;height:6px;width:100%;border-radius:9999px;background:var(--bg);overflow:hidden;" aria-hidden="true">
                                <div class="grow h-full rounded-full bg-accent" style="--i: {{ $loop->index }}; width: {{ min(100, $f['points'] / $maxPoints * 100) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-3 text-sm text-ink-2">Tidak ada faktor risiko yang menonjol.</p>
            @endif
        </section>

        @unless ($compact)
        <section class="rp-card" aria-labelledby="saran-{{ $audience }}">
            <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.5rem;">
                <h3 id="saran-{{ $audience }}" class="text-base font-semibold">{{ $audience === 'dpa' ? 'Bahan pendampingan' : 'Langkah yang bisa dicoba' }}</h3>
                @if ($result->llm_generated)
                    <span class="rp-badge" style="color:var(--accent);border-color:color-mix(in srgb, var(--accent) 40%, var(--line));background:color-mix(in srgb, var(--accent) 12%, var(--surface));font-weight:600;">AI (LLM)</span>
                @endif
            </div>
            <ol style="list-style:none;margin:1rem 0 0;padding:0;display:flex;flex-direction:column;gap:0.75rem;" class="text-sm">
                @foreach ($suggestions as $s)
                    <li style="display:flex;align-items:flex-start;gap:0.7rem;line-height:1.55;">
                        <span class="mono" style="width:22px;height:22px;border-radius:9999px;background:var(--bg);border:1px solid var(--line);display:inline-flex;align-items:center;justify-content:center;font-size:11px;color:var(--ink-2);flex-shrink:0;margin-top:1px;">{{ $loop->iteration }}</span>
                        <span class="text-ink-2">{{ $s }}</span>
                    </li>
                @endforeach
            </ol>
        </section>
        @endunless
    </div>

    <p class="mt-5 text-xs text-ink-3">
        Perkiraan berbasis pola data, bukan keputusan akademik &middot; {{ $result->data_as_of?->translatedFormat('j M Y') }}@if ($version) &middot; {{ $version }}@endif
    </p>
</article>