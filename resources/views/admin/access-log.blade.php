@extends('layouts.app')

@section('title', 'Catatan akses')

@section('content')
<h1 class="rise text-3xl sm:text-4xl">Catatan akses</h1>
<p class="rise mt-2 max-w-[62ch] text-ink-2" style="--i: 1">Catatan audit siapa membuka atau memperbarui data risiko mahasiswa mana. NIM ditampilkan untuk keterlacakan audit, sementara layanan ML dan LLM tetap tidak pernah menerima NIM.</p>

@if ($logs->isEmpty())
    <p class="mt-10 text-ink-2">Belum ada catatan.</p>
@else
    <div class="mt-10 overflow-x-auto">
        <table class="tbl min-w-[42rem]">
            <thead><tr><th>Waktu</th><th>Pengakses</th><th>NIM Mahasiswa</th><th>Aksi</th></tr></thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td class="num whitespace-nowrap">{{ $log->created_at->translatedFormat('j F Y, H:i') }}</td>
                        <td>{{ $log->actor?->name ?? 'Akun dihapus' }} <span class="text-sm text-ink-3">({{ $log->actor_role === 'dpa' ? 'DPA' : $log->actor_role }})</span></td>
                        <td class="mono font-medium">{{ $log->displayNim() }}</td>
                        <td>{{ $log->actionLabel() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('partials.pager', ['paginator' => $logs])
@endif
@endsection
