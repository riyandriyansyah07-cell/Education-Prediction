@extends('layouts.app')

@section('title', 'Tiket pengajuan akun')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="rise">Tiket pengajuan akun</h1>
        <p class="rise mt-1.5 text-sm text-ink-2" style="--i: 1">{{ $pendingCount }} tiket menunggu verifikasi sekretariat.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <nav style="display:flex;flex-wrap:wrap;gap:0.45rem;" aria-label="Saring status tiket">
            @foreach ([null => 'Semua', 'menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'] as $k => $lbl)
                <a href="{{ $k ? route('admin.tiket', ['status' => $k]) : route('admin.tiket') }}"
                   class="btn btn-sm {{ $status === $k ? 'btn-primary' : 'btn-quiet' }}">{{ $lbl }}</a>
            @endforeach
        </nav>
        @if (($processedCount ?? 0) > 0)
            <form method="POST" action="{{ route('admin.tiket.bersihkan') }}" onsubmit="return confirm('Hapus semua riwayat tiket selesai?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Bersihkan ({{ $processedCount }})</button>
            </form>
        @endif
    </div>
</div>

@if ($errors->any())
    <div class="mt-5 rounded-xl border border-bad/40 bg-bad/10 px-4 py-3 text-sm text-bad">
        {{ $errors->first() }}
    </div>
@endif

@if ($tickets->isEmpty())
    <div class="mt-8 bg-surface text-center text-sm text-ink-2" style="padding:2.25rem;border-style:dashed;">
        Belum ada tiket pengajuan akun pada filter ini.
    </div>
@else
    <div class="mt-6 space-y-4">
        @foreach ($tickets as $t)
            <article class="bg-surface" style="padding:1.3rem 1.45rem;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;">
                    <div style="min-width:0;flex:1;">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="mono font-semibold text-ink">{{ $t->ticket_code }}</span>
                            <span style="padding:0.16rem 0.55rem;border-radius:9999px;font-size:11px;border:1px solid var(--line-strong);background:var(--bg);color:var(--ink-2);">{{ $t->typeLabel() }}</span>
                            @if ($t->status === 'disetujui')
                                <span class="risk risk-chip risk-rendah">Disetujui</span>
                            @elseif ($t->status === 'ditolak')
                                <span class="risk risk-chip risk-tinggi">Ditolak</span>
                            @else
                                <span class="risk risk-chip risk-sedang">Menunggu</span>
                            @endif
                        </div>
                        <p class="mt-2 font-semibold text-ink">{{ $t->name }} <span class="mono text-sm font-normal text-ink-3">({{ $t->username }})</span></p>
                        <p class="mt-0.5 text-xs text-ink-2">
                            {{ collect([$t->program_studi, $t->email, $t->created_at->translatedFormat('j M Y, H:i')])->filter()->implode(' · ') }}
                        </p>
                        @if ($t->notes)
                            <p class="mt-2 border border-line bg-bg text-xs text-ink-2" style="border-radius:8px;padding:0.55rem 0.85rem;">{{ $t->notes }}</p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.tiket.hapus', $t) }}" style="flex-shrink:0;" onsubmit="return confirm('Hapus tiket {{ $t->ticket_code }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </div>

                @if ($t->status === 'menunggu')
                    <div class="mt-4 grid items-end gap-4 border-t border-line pt-4 lg:grid-cols-12">
                        <form method="POST" action="{{ route('admin.tiket.setujui', $t) }}" class="lg:col-span-8" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:0.65rem;">
                            @csrf
                            @if ($t->request_type === 'akun_mahasiswa')
                                <div style="flex:1;min-width:11rem;">
                                    <label class="label text-xs">Tetapkan DPA</label>
                                    <select name="dpa_id" class="input text-xs">
                                        <option value="">Belum ditetapkan</option>
                                        @foreach ($dpas as $d)
                                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div style="flex:1;min-width:12rem;">
                                <label class="label text-xs">Catatan persetujuan</label>
                                <input type="text" name="admin_note" placeholder="Opsional — tautan set sandi dikirim ke email" class="input text-sm">
                            </div>
                            <button type="submit" class="btn btn-primary shrink-0" style="min-height:42px;white-space:nowrap;">Setujui &amp; Kirim Tautan Sandi</button>
                        </form>

                        <form method="POST" action="{{ route('admin.tiket.tolak', $t) }}" class="lg:col-span-4" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:0.65rem;">
                            @csrf
                            <div style="min-width:0;flex:1;">
                                <label class="label text-xs">Alasan tolak</label>
                                <input type="text" name="admin_note" placeholder="NIM tidak valid" class="input text-sm" required>
                            </div>
                            <button type="submit" class="btn btn-danger shrink-0" style="min-height:42px;white-space:nowrap;">Tolak</button>
                        </form>
                    </div>
                @elseif ($t->admin_note)
                    <p class="mt-3 border-t border-line pt-3 text-xs text-ink-2">
                        <strong>{{ $t->processor?->name ?? 'Admin' }}:</strong> {{ $t->admin_note }}
                    </p>
                @endif
            </article>
        @endforeach
    </div>
    @include('partials.pager', ['paginator' => $tickets])
@endif
@endsection