@extends('layouts.app')

@section('title', 'Verifikasi bukti SKPI')

@section('content')
<style>
    .skpi-adm-card {
        border-radius: 12px;
        border: 1px solid var(--line);
        background: var(--surface);
        padding: 1.25rem 1.4rem;
    }
    .skpi-adm-panels {
        display: grid;
        gap: 0.9rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--line);
    }
    @media (min-width: 900px) {
        .skpi-adm-panels {
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
        }
    }
    .skpi-adm-box {
        border-radius: 10px;
        border: 1px solid var(--line);
        background: var(--bg);
        padding: 0.9rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
    }
    .skpi-adm-box.is-reject {
        border-color: color-mix(in srgb, var(--bad) 26%, var(--line));
    }
    .skpi-adm-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.65rem;
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
    <div class="mt-6" style="display:flex;flex-direction:column;gap:1rem;">
        @foreach ($docs as $doc)
            @php
                $stu = $studentsByRef->get($doc->student_ref);
                $badgeCls = $doc->verification_status === 'terverifikasi'
                    ? 'is-ok'
                    : ($doc->verification_status === 'ditolak' ? 'is-bad' : 'is-warn');
            @endphp
            <article class="skpi-adm-card">
                <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:0.85rem;">
                    <div style="min-width:0;">
                        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem;">
                            <span class="font-semibold text-sm text-ink">{{ $doc->typeLabel() }}</span>
                            <span class="skpi-adm-badge {{ $badgeCls }}">{{ $doc->statusLabel() }}</span>
                        </div>
                        <p class="mt-1.5 text-sm font-medium text-ink">
                            {{ $stu?->name ?? 'Mahasiswa' }}
                            @if ($stu?->username)
                                <span class="mono text-xs text-ink-3">({{ $stu->username }})</span>
                            @endif
                        </p>
                        <p class="mt-0.5 text-xs text-ink-3">
                            {{ $stu?->program_studi ?: 'Prodi belum diisi' }}
                            @if ($stu?->dpa) &middot; DPA: {{ $stu->dpa->name }} @endif
                            &middot; Diunggah {{ $doc->updated_at?->translatedFormat('j M Y, H:i') }}
                        </p>
                        <p class="mt-1.5 mono text-xs text-ink-2">
                            Berkas: {{ $doc->original_name }} ({{ $doc->formattedSize() }})
                        </p>
                        @if ($doc->verifier)
                            <p class="mt-1 text-xs text-ink-3">
                                {{ $doc->verification_status === 'terverifikasi' ? 'Diverifikasi' : 'Ditinjau' }} oleh <strong>{{ $doc->verifier->name }}</strong>
                                @if ($doc->verified_at) &middot; {{ $doc->verified_at->translatedFormat('j M Y, H:i') }} @endif
                                @if ($doc->verification_note) — "{{ $doc->verification_note }}" @endif
                            </p>
                        @endif
                    </div>

                    <a href="{{ route('skpi.dokumen.show', $doc) }}" target="_blank" rel="noopener" class="btn btn-quiet btn-sm" style="flex-shrink:0;">
                        Lihat Bukti &rarr;
                    </a>
                </div>

                <div class="skpi-adm-panels">
                    <form method="POST" action="{{ route('skpi.dokumen.verifikasi', $doc) }}" class="skpi-adm-box">
                        @csrf
                        <input type="hidden" name="decision" value="terverifikasi">
                        <div>
                            <label class="label text-xs" for="note-ok-{{ $doc->id }}">Catatan verifikasi (opsional)</label>
                            <input id="note-ok-{{ $doc->id }}" type="text" name="verification_note" maxlength="255" class="input text-sm" placeholder="Contoh: Sertifikat sesuai ketentuan FTII">
                        </div>
                        <div style="display:flex;justify-content:flex-end;">
                            <button type="submit" class="btn btn-primary btn-sm">Verifikasi Bukti</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('skpi.dokumen.verifikasi', $doc) }}" class="skpi-adm-box is-reject">
                        @csrf
                        <input type="hidden" name="decision" value="ditolak">
                        <div>
                            <label class="label text-xs" for="note-no-{{ $doc->id }}">Alasan minta perbaiki</label>
                            <input id="note-no-{{ $doc->id }}" type="text" name="verification_note" maxlength="255" class="input text-sm" placeholder="Contoh: Sertifikat buram / bukan TOEFL UHAMKA" required>
                        </div>
                        <div style="display:flex;justify-content:flex-end;">
                            <button type="submit" class="btn btn-danger btn-sm">Minta Perbaiki</button>
                        </div>
                    </form>
                </div>
            </article>
        @endforeach
    </div>
@endif
@endsection
