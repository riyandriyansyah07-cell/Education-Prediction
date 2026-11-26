@extends('layouts.app')

@section('title', 'Riwayat catatan bimbingan')

@section('content')
<style>
    .dpa-note-filter {
        margin-top: 1.15rem;
        padding: 0.75rem 0.9rem;
        border-radius: 10px;
        border: 1px solid var(--line);
        background: var(--surface);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.55rem;
    }
    .dpa-note-filter .input {
        padding: 0.42rem 0.7rem;
        font-size: 12.5px;
        min-height: 36px;
    }
    .dpa-note-search {
        flex: 1 1 220px;
        min-width: 170px;
    }
    .dpa-note-select {
        flex: 0 1 auto;
        width: auto;
        min-width: 132px;
    }
    @media (max-width: 640px) {
        .dpa-note-search,
        .dpa-note-select {
            flex: 1 1 100%;
            width: 100%;
        }
    }
    .dpa-note-card {
        border-radius: 10px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: 0.95rem 1.15rem;
    }
    .dpa-note-top {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.55rem 1rem;
    }
    .dpa-note-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.14rem 0.55rem;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 600;
        border: 1px solid var(--line);
        background: var(--bg);
        color: var(--ink-2);
        white-space: nowrap;
    }
    .dpa-note-badge.is-ok {
        color: var(--ok);
        border-color: color-mix(in srgb, var(--ok) 40%, var(--line));
        background: color-mix(in srgb, var(--ok) 12%, var(--surface));
    }
    .dpa-note-badge.is-warn {
        color: var(--warn);
        border-color: color-mix(in srgb, var(--warn) 40%, var(--line));
        background: color-mix(in srgb, var(--warn) 12%, var(--surface));
    }
</style>

@php
    $hasActiveFilter = $periodFilter !== 'semua' || $search !== '';
    $baseQuery = array_filter([
        'period' => $periodFilter !== 'semua' ? $periodFilter : null,
        'q' => $search !== '' ? $search : null,
    ]);
@endphp

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="rise text-3xl sm:text-4xl">Riwayat catatan bimbingan</h1>
        <p class="rise mt-1 text-sm text-ink-2" style="--i: 1">
            Arsip seluruh catatan konsultasi dan kesepakatan tindak lanjut mahasiswa bimbingan Anda.
        </p>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:0.4rem;">
        @foreach ([
            'semua' => 'Semua ('.$counts['semua'].')',
            'dijadwalkan' => 'Dijadwalkan ('.$counts['dijadwalkan'].')',
            'sudah_bimbingan' => 'Sudah bimbingan ('.$counts['sudah_bimbingan'].')',
            'selesai' => 'Selesai ('.$counts['selesai'].')',
        ] as $key => $label)
            <a href="{{ route('dpa.riwayat', array_merge($baseQuery, ['status' => $key])) }}"
               class="btn btn-sm {{ $statusFilter === $key ? 'btn-primary' : 'btn-quiet' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>

<form method="GET" action="{{ route('dpa.riwayat') }}" class="dpa-note-filter" aria-label="Filter riwayat bimbingan">
    <input type="hidden" name="status" value="{{ $statusFilter }}">
    <input type="search" name="q" value="{{ $search }}" class="input dpa-note-search" placeholder="Cari nama, NIM, atau topik bimbingan..." aria-label="Cari catatan bimbingan">

    <select name="period" class="input dpa-note-select" aria-label="Filter periode" onchange="this.form.submit()">
        <option value="semua" @selected($periodFilter === 'semua')>Semua waktu</option>
        <option value="hari" @selected($periodFilter === 'hari')>Hari ini</option>
        <option value="minggu" @selected($periodFilter === 'minggu')>Minggu ini</option>
        <option value="bulan" @selected($periodFilter === 'bulan')>Bulan ini</option>
    </select>

    <button type="submit" class="btn btn-primary btn-sm" style="min-height:36px;padding:0.4rem 0.85rem;font-size:12.5px;">Cari</button>

    @if ($hasActiveFilter)
        <a href="{{ route('dpa.riwayat', ['status' => $statusFilter]) }}" class="btn btn-quiet btn-sm" style="min-height:36px;padding:0.4rem 0.75rem;font-size:12px;">Reset</a>
    @endif
</form>

@if ($notes->isEmpty())
    <section class="mt-6 rounded-md border border-dashed border-line-strong bg-surface p-8 text-center">
        <h2 class="text-lg font-medium">Belum ada catatan bimbingan</h2>
        <p class="mt-1.5 text-sm text-ink-2">Catatan yang Anda simpan pada halaman detail mahasiswa bimbingan akan otomatis terkumpul di sini.</p>
    </section>
@else
    <div class="mt-5" style="display:flex;flex-direction:column;gap:0.65rem;">
        @foreach ($notes as $note)
            @php
                $stu = $advisees->get($note->student_ref);
                $badgeCls = in_array($note->follow_up_status, ['sudah_bimbingan', 'selesai'], true)
                    ? 'is-ok'
                    : ($note->follow_up_status === 'dijadwalkan' ? 'is-warn' : '');
            @endphp
            <article class="dpa-note-card">
                <div class="dpa-note-top">
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.45rem 0.6rem;min-width:0;">
                        <span style="font-size:15px;font-weight:600;color:var(--ink);">{{ $stu?->name ?? 'Mahasiswa' }}</span>
                        @if ($stu?->username)
                            <span class="mono text-xs text-ink-3">{{ $stu->username }}</span>
                        @endif
                        <span class="dpa-note-badge {{ $badgeCls }}">{{ $note->followUpLabel() }}</span>
                        <span class="mono text-xs text-ink-3">
                            &middot; {{ $note->guidance_date?->translatedFormat('j M Y') }}@if ($note->semester) &middot; Sem {{ $note->semester }}@endif
                        </span>
                    </div>

                    <div style="display:flex;align-items:center;gap:0.4rem;flex-shrink:0;">
                        @if ($stu)
                            <a href="{{ route('dpa.bimbingan.show', $stu) }}" class="btn btn-quiet btn-sm" style="min-height:30px;padding:0.25rem 0.65rem;font-size:12px;">
                                Detail &rarr;
                            </a>
                        @endif
                        <form method="POST" action="{{ route('dpa.riwayat.hapus', $note) }}" style="margin:0;" data-confirm="Hapus catatan bimbingan ini?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" style="min-height:30px;padding:0.25rem 0.65rem;font-size:12px;">Hapus</button>
                        </form>
                    </div>
                </div>

                <p class="mt-2 text-sm font-medium text-ink">{{ $note->topic }}</p>
                <p class="mt-1 text-xs text-ink-2" style="line-height:1.55;">{{ $note->follow_up_action }}</p>
            </article>
        @endforeach
    </div>

    @if ($notes->hasPages())
        <div class="mt-5">
            {{ $notes->links() }}
        </div>
    @endif
@endif
@endsection
