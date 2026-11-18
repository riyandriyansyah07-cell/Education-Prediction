@extends('layouts.app')

@section('title', 'Mahasiswa bimbingan')

@php
    $notifications = $notifications ?? collect();
    $unreadNotifIds = $unreadNotifIds ?? [];
    $statusCounts = $statusCounts ?? [];
    $statusFilter = $statusFilter ?? null;
@endphp

@section('content')
<h1 class="rise text-3xl sm:text-4xl">Monitoring mahasiswa bimbingan</h1>
<p class="rise mt-2 max-w-[62ch] text-ink-2" style="--i: 1">Hanya mahasiswa yang sudah memberi izin yang tampil. Kunjungan daftar bimbingan dicatat satu kali per sesi di log akses.</p>

@if ($pending > 0)
    <p class="mt-6 flex max-w-2xl items-start gap-3 rounded-md border border-line-strong bg-surface px-4 py-3 text-ink-2">
        <x-icon name="lock-key" class="mt-0.5" />
        <span>{{ $pending }} dari {{ $total }} mahasiswa bimbingan belum memberi izin. Namanya tidak ditampilkan.</span>
    </p>
@endif

@if ($notifications->isNotEmpty())
    <section class="mt-6 max-w-3xl rounded-md border border-bad/40 bg-bad/10 p-4" aria-label="Pemberitahuan kenaikan risiko">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-bad">Pemberitahuan Kenaikan Risiko ke Tinggi</h2>
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
    <section class="mt-10 max-w-2xl rounded-md border border-dashed border-line-strong p-8">
        <h2 class="text-2xl">Belum ada yang menyetujui.</h2>
        <p class="mt-3 text-ink-2">Daftar terisi saat mahasiswa bimbinganmu mengizinkan DPA melihat hasil risikonya. Kamu bisa mengingatkan mereka lewat jalur biasa.</p>
    </section>
@else
    <section class="mt-8 grid gap-4 sm:grid-cols-4" aria-label="Ringkasan status tindak lanjut kohort">
        @foreach ([
            'belum_dihubungi' => 'Belum dihubungi',
            'dijadwalkan' => 'Dijadwalkan',
            'sudah_bimbingan' => 'Sudah bimbingan',
            'selesai' => 'Selesai tindak lanjut',
        ] as $stKey => $stLabel)
            <a href="{{ route('dpa.bimbingan', array_filter(['tingkat' => $filter, 'status' => $statusFilter === $stKey ? null : $stKey])) }}"
               class="rounded-md border p-4 transition-colors {{ $statusFilter === $stKey ? 'border-accent bg-surface' : 'border-line bg-surface/50 hover:bg-surface' }}">
                <p class="text-xs text-ink-3">{{ $stLabel }}</p>
                <p class="num mono mt-1 text-2xl font-semibold">{{ $statusCounts[$stKey] ?? 0 }}</p>
            </a>
        @endforeach
    </section>

    <nav class="mt-8 flex flex-wrap gap-2" aria-label="Saring menurut tingkat risiko">
        @foreach ([null => 'Semua', 'tinggi' => 'Tinggi', 'sedang' => 'Sedang', 'rendah' => 'Rendah', 'belum' => 'Belum dihitung'] as $key => $label)
            @php $active = ($filter === ($key ?: null)); $count = $counts[$key ?: 'semua']; @endphp
            <a href="{{ $key ? route('dpa.bimbingan', array_filter(['tingkat' => $key, 'status' => $statusFilter])) : route('dpa.bimbingan', array_filter(['status' => $statusFilter])) }}"
               @if ($active) aria-current="true" @endif
               class="btn btn-sm {{ $active ? 'btn-primary' : 'btn-quiet' }}">{{ $label }} <span class="num opacity-80">{{ $count }}</span></a>
        @endforeach
    </nav>

    @if ($rows->isEmpty())
        <p class="mt-8 text-ink-2">Tidak ada mahasiswa pada saringan ini.</p>
    @else
        <div class="mt-6 overflow-x-auto">
            <table class="tbl min-w-[52rem]">
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Risiko</th>
                        <th>Perubahan</th>
                        <th>Status Tindak Lanjut</th>
                        <th>Semester</th>
                        <th>Faktor Utama</th>
                        <th><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php $res = $row['result']; $setting = $row['setting'] ?? null; @endphp
                        <tr>
                            <td>
                                <p class="font-medium">{{ $row['student']->name }}</p>
                                <p class="mono text-sm text-ink-3">{{ $row['student']->username }}</p>
                            </td>
                            <td><x-risk :level="$row['level']" /></td>
                            <td class="text-sm">
                                @if (! empty($row['escalated']))
                                    <span class="rounded border border-bad/40 bg-bad/10 px-2 py-0.5 text-xs font-medium text-bad">
                                        &uarr; Naik dari {{ $row['previous_level'] }}
                                    </span>
                                @elseif (! empty($row['previous_level']))
                                    <span class="text-xs text-ink-3">Stabil ({{ $row['previous_level'] }})</span>
                                @else
                                    <span class="text-xs text-ink-3">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="rounded border border-line-strong bg-surface px-2.5 py-1 text-xs font-medium">
                                    {{ \App\Models\StudentSetting::followUpLabelFor($row['follow_up_status'] ?? 'belum_dihubungi') }}
                                </span>
                            </td>
                            <td class="num">{{ $res?->semester ?? '-' }}</td>
                            <td class="text-ink-2">{{ $res && count($res->factors) ? $res->factors[0]['label'] : ($res ? 'Tidak ada yang menonjol' : '-') }}</td>
                            <td class="text-right">
                                @if ($res)
                                    <a href="{{ route('dpa.bimbingan.show', $row['student']) }}" class="btn btn-quiet btn-sm">Pantau &amp; Catat <x-icon name="arrow-right" size="size-4" /></a>
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
