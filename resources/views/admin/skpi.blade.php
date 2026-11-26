@extends('layouts.app')

@section('title', 'Verifikasi bukti SKPI')

@section('content')
<style>
    .skpi-adm-card {
        border-radius: 10px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: 0.9rem 1.15rem;
    }
    .skpi-adm-top {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.65rem 1rem;
    }
    .skpi-adm-meta {
        margin-top: 0.25rem;
        font-size: 12px;
        color: var(--ink-3);
        line-height: 1.45;
    }
    .skpi-adm-actions-bar {
        display: grid;
        gap: 0.6rem;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid var(--line);
    }
    @media (min-width: 900px) {
        .skpi-adm-actions-bar {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }
    }
    .skpi-adm-inline-form {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 0.45rem;
    }
    .skpi-adm-inline-form .input {
        flex: 1 1 auto;
        min-width: 0;
        padding: 0.4rem 0.65rem;
        font-size: 12.5px;
        min-height: 34px;
    }
    .skpi-adm-inline-form .btn {
        flex-shrink: 0;
        padding: 0.4rem 0.75rem;
        font-size: 12px;
        min-height: 34px;
        white-space: nowrap;
    }
    @media (max-width: 560px) {
        .skpi-adm-inline-form {
            flex-wrap: wrap;
        }
        .skpi-adm-inline-form .btn {
            width: 100%;
        }
    }
    .skpi-adm-badge {
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
    .skpi-adm-badge.is-ok {
        color: var(--ok);
        border-color: color-mix(in srgb, var(--ok) 40%, var(--line));
        background: color-mix(in srgb, var(--ok) 12%, var(--surface));
    }
    .skpi-adm-badge.is-warn {
        color: var(--warn);
        border-color: color-mix(in srgb, var(--warn) 40%, var(--line));
        background: color-mix(in srgb, var(--warn) 12%, var(--surface));
    }
    .skpi-adm-badge.is-bad {
        color: var(--bad);
        border-color: color-mix(in srgb, var(--bad) 40%, var(--line));
        background: color-mix(in srgb, var(--bad) 12%, var(--surface));
    }
    .skpi-adm-toggle > summary {
        list-style: none;
        cursor: pointer;
        user-select: none;
        font-size: 11.5px;
        color: var(--ink-3);
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        margin-top: 0.55rem;
    }
    .skpi-adm-toggle > summary::-webkit-details-marker {
        display: none;
    }
    .skpi-adm-toggle > summary:hover {
        color: var(--ink);
    }
</style>

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="rise text-3xl sm:text-4xl">Verifikasi bukti SKPI</h1>
        <p class="rise mt-1 text-sm text-ink-2" style="--i: 1">
            {{ $counts['menunggu'] }} berkas sertifikat menunggu verifikasi Sekretariat atau DPA.
        </p>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:0.45rem;">
        @foreach ([
            'semua' => 'Semua ('.$counts['semua'].')',
            'menunggu' => 'Menunggu ('.$counts['menunggu'].')',
            'terverifikasi' => 'Terverifikasi ('.$counts['terverifikasi'].')',
            'ditolak' => 'Perlu diperbaiki ('.$counts['ditolak'].')',
        ] as $key => $label)
            <a href="{{ route('admin.skpi', ['status' => $key]) }}"
               class="btn btn-sm {{ $statusFilter === $key ? 'btn-primary' : 'btn-quiet' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>

@if ($docs->isEmpty())
    <section class="mt-8 rounded-md border border-dashed border-line-strong bg-surface p-8 text-center">
        <h2 class="text-lg font-medium">Belum ada berkas bukti SKPI</h2>
        <p class="mt-1.5 text-sm text-ink-2">Berkas yang diunggah mahasiswa pada menu Pengayaan &amp; SKPI akan muncul di sini.</p>
    </section>
@else
    <div class="mt-5" style="display:flex;flex-direction:column;gap:0.65rem;">
        @foreach ($docs as $doc)
            @php
                $stu = $studentsByRef->get($doc->student_ref);
                $isPending = $doc->verification_status === 'menunggu';
                $badgeCls = $doc->verification_status === 'terverifikasi'
                    ? 'is-ok'
                    : ($doc->verification_status === 'ditolak' ? 'is-bad' : 'is-warn');
            @endphp
            <article class="skpi-adm-card">
                <div class="skpi-adm-top">
                    <div style="min-width:0;flex:1 1 280px;">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.45rem 0.6rem;">
                            <span class="font-semibold text-sm text-ink">{{ $doc->typeLabel() }}</span>
                            <span class="skpi-adm-badge {{ $badgeCls }}">{{ $doc->statusLabel() }}</span>
                            <span class="text-xs text-ink-2">
                                &middot; <strong>{{ $stu?->name ?? 'Mahasiswa' }}</strong>
                                @if ($stu?->username)
                                    <span class="mono text-ink-3">({{ $stu->username }})</span>
                                @endif
                            </span>
                        </div>

                        <p class="skpi-adm-meta">
                            {{ $stu?->program_studi ?: 'Prodi belum diisi' }}
                            @if ($stu?->dpa) &middot; DPA: {{ $stu->dpa->name }} @endif
                            &middot; <span class="mono">{{ $doc->original_name }}</span> ({{ $doc->formattedSize() }})
                            &middot; {{ $doc->updated_at?->translatedFormat('j M Y, H:i') }}
                        </p>

                        @if ($doc->verifier)
                            <p class="skpi-adm-meta" style="margin-top:0.15rem;">
                                {{ $doc->verification_status === 'terverifikasi' ? 'Diverifikasi' : 'Ditinjau' }} oleh <strong class="text-ink-2">{{ $doc->verifier->name }}</strong>
                                @if ($doc->verified_at) &middot; {{ $doc->verified_at->translatedFormat('j M Y, H:i') }} @endif
                                @if ($doc->verification_note) &mdash; "{{ $doc->verification_note }}" @endif
                            </p>
                        @endif
                    </div>

                    <a href="{{ route('skpi.dokumen.show', $doc) }}" target="_blank" rel="noopener" class="btn btn-quiet btn-sm" style="flex-shrink:0;min-height:32px;padding:0.3rem 0.7rem;font-size:12px;">
                        Lihat Bukti &rarr;
                    </a>
                </div>

                @if ($isPending)
                    <div class="skpi-adm-actions-bar">
                        <form method="POST" action="{{ route('skpi.dokumen.verifikasi', $doc) }}" class="skpi-adm-inline-form">
                            @csrf
                            <input type="hidden" name="decision" value="terverifikasi">
                            <input type="text" name="verification_note" maxlength="255" class="input" aria-label="Catatan verifikasi" placeholder="Catatan verifikasi (opsional)">
                            <button type="submit" class="btn btn-primary">Verifikasi</button>
                        </form>

                        <form method="POST" action="{{ route('skpi.dokumen.verifikasi', $doc) }}" class="skpi-adm-inline-form">
                            @csrf
                            <input type="hidden" name="decision" value="ditolak">
                            <input type="text" name="verification_note" maxlength="255" class="input" aria-label="Alasan perbaikan" placeholder="Alasan minta perbaiki..." required>
                            <button type="submit" class="btn btn-danger">Minta Perbaiki</button>
                        </form>
                    </div>
                @else
                    <details class="skpi-adm-toggle">
                        <summary>Ubah keputusan verifikasi &#9662;</summary>
                        <div class="skpi-adm-actions-bar">
                            <form method="POST" action="{{ route('skpi.dokumen.verifikasi', $doc) }}" class="skpi-adm-inline-form">
                                @csrf
                                <input type="hidden" name="decision" value="terverifikasi">
                                <input type="text" name="verification_note" maxlength="255" class="input" aria-label="Catatan verifikasi" placeholder="Catatan verifikasi (opsional)">
                                <button type="submit" class="btn btn-primary">Verifikasi</button>
                            </form>

                            <form method="POST" action="{{ route('skpi.dokumen.verifikasi', $doc) }}" class="skpi-adm-inline-form">
                                @csrf
                                <input type="hidden" name="decision" value="ditolak">
                                <input type="text" name="verification_note" maxlength="255" class="input" aria-label="Alasan perbaikan" placeholder="Alasan minta perbaiki..." required>
                                <button type="submit" class="btn btn-danger">Minta Perbaiki</button>
                            </form>
                        </div>
                    </details>
                @endif
            </article>
        @endforeach
    </div>
@endif
@endsection
