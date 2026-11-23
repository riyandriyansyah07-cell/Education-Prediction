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

<article class="risk-{{ $level }}">
    <div class="border-l-4 pl-5 sm:pl-7" style="border-color: var(--c)">
        <div class="flex flex-wrap items-center gap-2 text-xs text-ink-2">
            <span>Semester {{ $result->semester }}</span>
            <span>&middot;</span>
            <span class="rounded border border-line-strong bg-surface px-2 py-0.5 font-medium">{{ $trustLabel }}</span>
            <span class="rounded border border-line bg-surface px-2 py-0.5 text-ink-3">{{ $completeness }}</span>
            @if ($result->is_projection)
                <span class="rounded border border-accent/40 bg-accent-soft px-2 py-0.5 font-medium text-accent">Proyeksi</span>
            @endif
        </div>

        <div class="mt-2 flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
            <h2 class="risk text-4xl sm:text-5xl">{{ ['rendah' => 'Risiko rendah', 'sedang' => 'Risiko sedang', 'tinggi' => 'Risiko tinggi'][$level] }}</h2>
            <p class="num text-ink">
                <span class="mono text-3xl font-medium">{{ \App\Support\Fmt::num($score, 1) }}</span><span class="text-ink-3"> / 10</span>
            </p>
        </div>

        <p class="mt-3 max-w-[60ch] text-sm text-ink-2">
            {{ $audience === 'student' ? ($result->features['encouragement'] ?? $copy[$level]) : $copy[$level] }}
        </p>

        @if ($result->projection_gap_flagged)
            <p class="mt-3 rounded-md border border-warn/40 bg-warn/10 px-3 py-2 text-xs text-ink">
                Selisih cukup besar antara proyeksi mandiri dan nilai akhir KHS resmi.
            </p>
        @endif

        <div class="mt-6" role="img" aria-label="Skor risiko {{ \App\Support\Fmt::num($score, 1) }} dari 10.">
            <div class="relative pb-6">
                <div class="relative h-3 overflow-hidden rounded-md">
                    <div class="absolute inset-y-0 left-0" style="width: {{ $pos($thresholds['sedang']) }}%; background: color-mix(in oklab, var(--ok) 32%, transparent)"></div>
                    <div class="absolute inset-y-0" style="left: {{ $pos($thresholds['sedang']) }}%; width: {{ $pos($thresholds['tinggi']) - $pos($thresholds['sedang']) }}%; background: color-mix(in oklab, var(--warn) 32%, transparent)"></div>
                    <div class="absolute inset-y-0 right-0" style="left: {{ $pos($thresholds['tinggi']) }}%; background: color-mix(in oklab, var(--bad) 32%, transparent)"></div>
                </div>
                <span class="absolute -top-1 h-5 w-0.5 -translate-x-1/2 bg-ink" style="left: {{ $pos($score) }}%"></span>
                <div class="num absolute inset-x-0 bottom-0 h-4 text-xs text-ink-3">
                    <span class="absolute left-0">0</span>
                    <span class="absolute -translate-x-1/2" style="left: {{ $pos($thresholds['sedang']) }}%">{{ \App\Support\Fmt::num($thresholds['sedang'], 1) }}</span>
                    <span class="absolute -translate-x-1/2" style="left: {{ $pos($thresholds['tinggi']) }}%">{{ \App\Support\Fmt::num($thresholds['tinggi'], 1) }}</span>
                    <span class="absolute right-0">10</span>
                </div>
            </div>
        </div>
    </div>

    <section class="mt-9" aria-labelledby="faktor-{{ $audience }}">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h3 id="faktor-{{ $audience }}" class="text-xl">{{ $audience === 'dpa' ? 'Faktor utama' : 'Penyebab utama' }}</h3>
            <a href="{{ route('metodologi') }}" class="text-xs font-medium text-accent hover:underline">Metodologi &rarr;</a>
        </div>
        @if ($explanationUnavailable)
            <p class="mt-3 text-sm text-ink-2">Penjelasan belum tersedia. Klik Hitung ulang.</p>
        @elseif (count($factors))
            <ul class="mt-4 space-y-4">
                @foreach ($factors as $f)
                    <li class="rise" style="--i: {{ $loop->index }}">
                        <div class="flex items-baseline justify-between gap-4">
                            <p class="font-medium">{{ $f['label'] }}</p>
                            <p class="num mono shrink-0 text-xs text-ink-2">+{{ \App\Support\Fmt::num($f['points'], 2) }}</p>
                        </div>
                        <p class="mt-0.5 text-sm text-ink-2">{{ $f['detail'] }}</p>
                        <div class="mt-2 h-1.5 w-full" aria-hidden="true">
                            <div class="grow h-full rounded-md bg-accent" style="--i: {{ $loop->index }}; width: {{ min(100, $f['points'] / $maxPoints * 100) }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-3 text-sm text-ink-2">Tidak ada faktor risiko yang menonjol.</p>
        @endif
    </section>

    @unless ($compact)
    <section class="mt-9" aria-labelledby="saran-{{ $audience }}">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 id="saran-{{ $audience }}" class="text-xl">{{ $audience === 'dpa' ? 'Bahan pendampingan' : 'Langkah yang bisa dicoba' }}</h3>
            @if ($result->llm_generated)
                <span class="rounded border border-accent/40 bg-accent-soft px-2 py-0.5 text-xs font-medium text-accent">AI (LLM)</span>
            @endif
        </div>
        <ol class="mt-4 space-y-2.5 text-sm">
            @foreach ($suggestions as $s)
                <li class="flex gap-3">
                    <span class="mono mt-0.5 w-5 shrink-0 text-ink-3">{{ $loop->iteration }}</span>
                    <span class="max-w-[62ch]">{{ $s }}</span>
                </li>
            @endforeach
        </ol>
    </section>
    @endunless

    <p class="mt-7 border-t border-line pt-3 text-xs text-ink-3">
        Perkiraan berbasis pola data, bukan keputusan akademik &middot; {{ $result->data_as_of?->translatedFormat('j M Y') }}@if ($version) &middot; {{ $version }}@endif
    </p>
</article>