@extends('layouts.app')

@section('title', 'Mahasiswa bimbingan')

@php
    $notifications = $notifications ?? collect();
    $unreadNotifIds = $unreadNotifIds ?? [];
    $statusCounts = $statusCounts ?? [];
    $statusFilter = $statusFilter ?? null;
@endphp

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="rise text-3xl sm:text-4xl">Mahasiswa bimbingan</h1>
        <p class="rise mt-1 text-ink-2" style="--i: 1">Menampilkan mahasiswa yang memberi izin.</p>
    </div>
    @if ($pending > 0)
        <span class="rounded-md border border-line-strong bg-surface px-3 py-1.5 text-xs text-ink-2">
            {{ $pending }} dari {{ $total }} belum memberi izin
        </span>
    @endif
</div>

@if ($notifications->isNotEmpty())
    <section class="mt-6 rounded-md border border-bad/40 bg-bad/10 p-4" aria-label="Pemberitahuan kenaikan risiko">
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

@if ($counts['semua'] === 0)
    <section class="mt-8 rounded-md border border-dashed border-line-strong bg-surface p-8 text-center">
        <h2 class="text-xl font-medium">Belum ada mahasiswa yang memberi izin</h2>
        <p class="mt-2 text-sm text-ink-2">Daftar otomatis terisi saat mahasiswa mengaktifkan izin DPA di menu Privasi.</p>
    </section>
@else
    <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Ringkasan status tindak lanjut">
        @foreach ([
            'belum_dihubungi' => 'Belum dihubungi',
            'dijadwalkan' => 'Dijadwalkan',
            'sudah_bimbingan' => 'Sudah bimbingan',
            'selesai' => 'Selesai',
        ] as $stKey => $stLabel)
            <a href="{{ route('dpa.bimbingan', array_filter(['tingkat' => $filter, 'status' => $statusFilter === $stKey ? null : $stKey])) }}"
               class="rounded-md border p-4 transition-colors {{ $statusFilter === $stKey ? 'border-accent bg-surface' : 'border-line bg-surface/50 hover:bg-surface' }}">
                <p class="text-xs text-ink-3">{{ $stLabel }}</p>
                <p class="num mono mt-1 text-2xl font-semibold">{{ $statusCounts[$stKey] ?? 0 }}</p>
            </a>
        @endforeach
    </section>

    <nav class="mt-6 flex flex-wrap gap-2" aria-label="Saring menurut tingkat risiko">
        @foreach ([null => 'Semua', 'tinggi' => 'Tinggi', 'sedang' => 'Sedang', 'rendah' => 'Rendah', 'belum' => 'Belum dihitung'] as $key => $label)
            @php $active = ($filter === ($key ?: null)); $count = $counts[$key ?: 'semua']; @endphp
            <a href="{{ $key ? route('dpa.bimbingan', array_filter(['tingkat' => $key, 'status' => $statusFilter])) : route('dpa.bimbingan', array_filter(['status' => $statusFilter])) }}"
               @if ($active) aria-current="true" @endif
               class="btn btn-sm {{ $active ? 'btn-primary' : 'btn-quiet' }}">{{ $label }} <span class="num opacity-80">{{ $count }}</span></a>
        @endforeach
    </nav>

    @if ($rows->isEmpty())
        <p class="mt-8 text-sm text-ink-2">Tidak ada mahasiswa pada filter ini.</p>
    @else
        <div class="mt-6 overflow-x-auto">
            <table class="tbl min-w-[52rem]">
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Risiko</th>
                        <th>Tren</th>
                        <th>Tindak Lanjut</th>
                        <th>Sem</th>
                        <th>Faktor Utama</th>
                        <th><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php $res = $row['result']; @endphp
                        <tr>
                            <td>
                                <p class="font-medium">{{ $row['student']->name }}</p>
                                <p class="mono text-xs text-ink-3">{{ $row['student']->username }}</p>
                            </td>
                            <td><x-risk :level="$row['level']" /></td>
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
                                <span class="rounded border border-line-strong bg-surface px-2 py-0.5 text-xs">
                                    {{ \App\Models\StudentSetting::followUpLabelFor($row['follow_up_status'] ?? 'belum_dihubungi') }}
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
    @endif
@endif
@endsection