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
        <a href="{{ route('mhs.impor') }}" class="btn btn-primary mt-5"><x-icon name="upload-simple" /> Impor data</a>
    </section>
@else
    @php
        $sortedResults = $results->sortBy('semester')->values();
        $ipsSeries = $sortedResults->map(fn ($r) => (float) ($r->features['ips'] ?? 0))->values();
        $n = $ipsSeries->count();
        $w = 680; $h = 150; $padX = 34; $padY = 24;
        $lo = max(0, ($ipsSeries->min() ?? 0) - 0.35);
        $hi = min(4, ($ipsSeries->max() ?? 4) + 0.35);
        $span = max(0.5, $hi - $lo);
        $pts = $ipsSeries->map(fn ($v, $i) => [
            $padX + ($n > 1 ? $i * ($w - 2 * $padX) / ($n - 1) : ($w - 2 * $padX) / 2),
            $padY + (1 - ($v - $lo) / $span) * ($h - 2 * $padY - 12),
        ]);
    @endphp

    @if ($n > 0)
        <section class="mt-6 bg-surface" style="padding:1.35rem 1.5rem;">
            <div style="display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:0.5rem;">
                <h2 class="text-base font-semibold">Grafik Perkembangan IPS per Semester</h2>
                <span class="mono text-xs text-ink-3">{{ $n }} semester tercatat</span>
            </div>
            <div class="mt-4">
                <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full text-accent" style="max-height:190px;" role="img" aria-label="Grafik IPS per semester">
                    <line x1="{{ $padX }}" y1="{{ $padY }}" x2="{{ $w - $padX }}" y2="{{ $padY }}" stroke="var(--line)" stroke-dasharray="3 4" />
                    <line x1="{{ $padX }}" y1="{{ $h - $padY - 12 }}" x2="{{ $w - $padX }}" y2="{{ $h - $padY - 12 }}" stroke="var(--line)" />
                    @if ($n > 1)
                        <polyline fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" points="{{ $pts->map(fn ($p) => round($p[0], 1).','.round($p[1], 1))->implode(' ') }}" />
                    @endif
                    @foreach ($pts as $i => $p)
                        <circle cx="{{ round($p[0], 1) }}" cy="{{ round($p[1], 1) }}" r="5" fill="var(--surface)" stroke="currentColor" stroke-width="2.5" />
                        <text x="{{ round($p[0], 1) }}" y="{{ round($p[1], 1) - 11 }}" text-anchor="middle" fill="var(--ink)" font-size="11" font-family="ui-monospace, SFMono-Regular, monospace" font-weight="600">{{ \App\Support\Fmt::num($ipsSeries[$i]) }}</text>
                        <text x="{{ round($p[0], 1) }}" y="{{ $h - 4 }}" text-anchor="middle" fill="var(--ink-3)" font-size="11">Sem {{ $sortedResults[$i]->semester }}</text>
                    @endforeach
                </svg>
            </div>
        </section>
    @endif

    <div class="mt-6 overflow-x-auto">
        <table class="tbl min-w-[48rem]">
            <thead>
                <tr><th>Semester</th><th>SKS</th><th>IPS</th><th>Kehadiran</th><th>Risiko</th><th>Skor</th><th>Data per</th><th>Model</th><th><span class="sr-only">Aksi</span></th></tr>
            </thead>
            <tbody>
                @foreach ($results as $r)
                    <tr>
                        <td class="num font-medium">Sem {{ $r->semester }}</td>
                        <td class="num">{{ $r->features['credits'] ?? '-' }}</td>
                        <td class="num mono">{{ \App\Support\Fmt::num($r->features['ips'] ?? 0) }}</td>
                        <td class="num">{{ \App\Support\Fmt::pct($r->features['attendance'] ?? 0) }}</td>
                        <td><x-risk :level="$r->level" /></td>
                        <td class="num mono">{{ \App\Support\Fmt::num($r->score, 1) }}</td>
                        <td class="num whitespace-nowrap">{{ $r->data_as_of?->translatedFormat('j M Y') }}</td>
                        <td class="mono text-xs text-ink-2">{{ $r->modelVersion?->version ?? '-' }}</td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('mhs.riwayat.hapus', $r->semester) }}" data-confirm="Hapus data semester {{ $r->semester }}?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection