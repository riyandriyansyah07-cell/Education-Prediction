@extends('layouts.app')

@section('title', 'Tiket pengajuan akun')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="rise text-3xl sm:text-4xl">Tiket akun</h1>
        <p class="rise mt-1 text-ink-2" style="--i: 1">{{ $pendingCount }} menunggu verifikasi.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <nav class="flex flex-wrap gap-2" aria-label="Saring status tiket">
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
    <div class="mt-6 rounded-md border border-bad/40 bg-bad/10 px-4 py-3 text-sm text-bad">
        {{ $errors->first() }}
    </div>
@endif

@if ($tickets->isEmpty())
    <p class="mt-10 text-sm text-ink-2">Belum ada tiket.</p>
@else
    <div class="mt-8 space-y-4">
        @foreach ($tickets as $t)
            <div class="rounded-md border border-line bg-surface p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="mono font-semibold">{{ $t->ticket_code }}</span>
                            <span class="rounded border border-line-strong bg-bg px-2 py-0.5 text-xs">{{ $t->typeLabel() }}</span>
                            @if ($t->status === 'disetujui')
                                <span class="risk risk-chip risk-rendah">Disetujui</span>
                            @elseif ($t->status === 'ditolak')
                                <span class="risk risk-chip risk-tinggi">Ditolak</span>
                            @else
                                <span class="risk risk-chip risk-sedang">Menunggu</span>
                            @endif
                        </div>
                        <p class="mt-2 font-medium">{{ $t->name }} <span class="mono text-sm text-ink-3">({{ $t->username }})</span></p>
                        <p class="mt-0.5 text-xs text-ink-2">
                            {{ collect([$t->program_studi, $t->email, $t->created_at->translatedFormat('j M Y, H:i')])->filter()->implode(' · ') }}
                        </p>
                        @if ($t->notes)
                            <p class="mt-1.5 text-xs text-ink-2">{{ $t->notes }}</p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.tiket.hapus', $t) }}" onsubmit="return confirm('Hapus tiket {{ $t->ticket_code }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-quiet btn-sm text-bad">Hapus</button>
                    </form>
                </div>

                @if ($t->status === 'menunggu')
                    <div class="mt-4 grid items-end gap-4 border-t border-line pt-4 lg:grid-cols-12">
                        <form method="POST" action="{{ route('admin.tiket.setujui', $t) }}" class="flex flex-wrap items-end gap-2 lg:col-span-8">
                            @csrf
                            <div class="flex-1" style="min-width: 8.5rem;">
                                <label class="label text-xs">Sandi awal</label>
                                <input type="text" name="password" value="password123" minlength="8" class="input h-9 w-full mono text-sm" required>
                            </div>
                            @if ($t->request_type === 'akun_mahasiswa')
                                <div class="flex-1" style="min-width: 11rem;">
                                    <label class="label text-xs">DPA</label>
                                    <select name="dpa_id" class="input h-9 w-full text-xs">
                                        <option value="">Belum ditetapkan</option>
                                        @foreach ($dpas as $d)
                                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="flex-1" style="min-width: 10rem;">
                                <label class="label text-xs">Catatan</label>
                                <input type="text" name="admin_note" placeholder="Opsional" class="input h-9 w-full text-sm">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm h-9 shrink-0">Setujui</button>
                        </form>

                        <form method="POST" action="{{ route('admin.tiket.tolak', $t) }}" class="flex items-end gap-2 lg:col-span-4">
                            @csrf
                            <div class="min-w-0 flex-1">
                                <label class="label text-xs">Alasan tolak</label>
                                <input type="text" name="admin_note" placeholder="NIM tidak valid" class="input h-9 w-full text-sm" required>
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm h-9 shrink-0">Tolak</button>
                        </form>
                    </div>
                @elseif ($t->admin_note)
                    <p class="mt-3 border-t border-line pt-3 text-xs text-ink-2">
                        {{ $t->processor?->name ?? 'Admin' }}: {{ $t->admin_note }}
                    </p>
                @endif
            </div>
        @endforeach
    </div>
    @include('partials.pager', ['paginator' => $tickets])
@endif
@endsection