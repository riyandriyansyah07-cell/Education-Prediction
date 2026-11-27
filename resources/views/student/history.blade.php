@extends('layouts.app')

@section('title', 'Riwayat semester')

@section('content')
<div>
    <h1 class="rise">Riwayat semester</h1>
    <p class="rise mt-1.5 max-w-[60ch] text-sm text-ink-2" style="--i: 1">Hasil tiap semester disimpan lengkap dengan tanggal data dan versi model yang dipakai.</p>
</div>

@if ($results->isEmpty())
    <section class="mt-8 max-w-2xl bg-surface" style="padding:2rem;border-style:dashed;">
        <h2 class="text-lg font-semibold">Belum ada riwayat.</h2>
        <p class="mt-2 text-sm text-ink-2">Riwayat terbentuk setelah kamu mengimpor data akademik.</p>
        <a href="{{ route('mhs.impor') }}" class="btn btn-primary mt-5" style="border-radius:9999px !important;"><x-icon name="upload-simple" /> Impor data</a>
    </section>
@else
    @php
        $sortedResults = $results->sortBy('semester')->values();
        $ipsSeries = $sortedResults->map(fn ($r) => (float) ($r->features['ips'] ?? 0))->values();
        $n = $ipsSeries->count();
        $w = 720; $h = 200; $padLeft = 56; $padRight = 40; $padTop = 28; $padBottom = 34;
        $minVal = $ipsSeries->min() ?? 0;
        $lo = max(0, floor(($minVal - 0.35) * 2) / 2);
        $hi = 4.0;
        $span = max(0.5, $hi - $lo);
        $plotW = $w - $padLeft - $padRight;
        $plotH = $h - $padTop - $padBottom;
        $pts = $ipsSeries->map(fn ($v, $i) => [
            $padLeft + ($n > 1 ? $i * $plotW / ($n - 1) : $plotW / 2),
            $padTop + (1 - ($v - $lo) / $span) * $plotH,
        ]);
        $ticks = [];
        for ($t = $hi; $t >= $lo - 0.001; $t -= 0.5) {
            if (count($ticks) >= 5) break;
            $ticks[] = round($t, 1);
        }
        $polyPoints = $pts->map(fn ($p) => round($p[0], 1).','.round($p[1], 1))->implode(' ');
        $areaPoints = $n > 1
            ? round($pts->first()[0], 1).','.($padTop + $plotH).' '.$polyPoints.' '.round($pts->last()[0], 1).','.($padTop + $plotH)
            : '';
    @endphp

    @if ($n > 0)
        <section class="mt-6 bg-surface" style="padding:1.5rem 1.65rem;">
            <div style="display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:0.5rem;">
                <h2 class="text-base font-semibold text-ink">Grafik Perkembangan IPS per Semester</h2>
                <span class="mono text-xs text-ink-3">{{ $n }} semester tercatat</span>
            </div>
            <div class="mt-4">
                <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full text-accent" style="max-height:220px;" role="img" aria-label="Grafik IPS per semester">
                    <defs>
                        <linearGradient id="ipsAreaGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="currentColor" stop-opacity="0.14" />
                            <stop offset="100%" stop-color="currentColor" stop-opacity="0.0" />
                        </linearGradient>
                    </defs>

                    @foreach ($ticks as $tv)
                        @php $ty = round($padTop + (1 - ($tv - $lo) / $span) * $plotH, 1); @endphp
                        <line x1="{{ $padLeft }}" y1="{{ $ty }}" x2="{{ $w - $padRight }}" y2="{{ $ty }}" stroke="var(--line)" stroke-dasharray="3 4" />
                        <text x="{{ $padLeft - 12 }}" y="{{ $ty + 3.5 }}" text-anchor="end" fill="var(--ink-3)" font-size="11" font-family="ui-monospace, SFMono-Regular, monospace">{{ \App\Support\Fmt::num($tv, 1) }}</text>
                    @endforeach

                    @if ($n > 1)
                        <polygon points="{{ $areaPoints }}" fill="url(#ipsAreaGrad)" />
                        <polyline fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" points="{{ $polyPoints }}" />
                    @endif

                    @foreach ($pts as $i => $p)
                        <circle cx="{{ round($p[0], 1) }}" cy="{{ round($p[1], 1) }}" r="5" fill="var(--surface)" stroke="currentColor" stroke-width="2.5" />
                        <text x="{{ round($p[0], 1) }}" y="{{ round($p[1], 1) - 12 }}" text-anchor="middle" fill="var(--ink)" font-size="11.5" font-family="ui-monospace, SFMono-Regular, monospace" font-weight="700">{{ \App\Support\Fmt::num($ipsSeries[$i]) }}</text>
                        <text x="{{ round($p[0], 1) }}" y="{{ $h - 8 }}" text-anchor="middle" fill="var(--ink-3)" font-size="11">Sem {{ $sortedResults[$i]->semester }}</text>
                    @endforeach
                </svg>
            </div>
        </section>
    @endif

    <section class="mt-6 bg-surface" style="padding:1.25rem 1.4rem;">
        <div class="overflow-x-auto" style="border:none !important;box-shadow:none !important;">
            <table class="tbl min-w-[48rem]">
                <thead>
                    <tr><th>Semester</th><th>SKS</th><th>IPS</th><th>Kehadiran</th><th>Risiko</th><th>Skor</th><th>Data per</th><th>Model</th><th class="text-right">Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($results as $r)
                        <tr>
                            <td class="num font-semibold text-ink">Sem {{ $r->semester }}</td>
                            <td class="num">{{ $r->features['credits'] ?? '-' }}</td>
                            <td class="num mono font-semibold text-ink">{{ \App\Support\Fmt::num($r->features['ips'] ?? 0) }}</td>
                            <td class="num">{{ \App\Support\Fmt::pct($r->features['attendance'] ?? 0) }}</td>
                            <td><x-risk :level="$r->level" /></td>
                            <td class="num mono font-semibold text-ink">{{ \App\Support\Fmt::num($r->score, 1) }}</td>
                            <td class="num whitespace-nowrap text-ink-2">{{ $r->data_as_of?->translatedFormat('j M Y') }}</td>
                            <td class="mono text-xs text-ink-3">{{ $r->modelVersion?->version ?? '-' }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('mhs.riwayat.hapus', $r->semester) }}" data-confirm="Hapus data semester {{ $r->semester }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" style="border-radius:10px !important;">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
@endsection