@extends('layouts.app')

@section('title', 'Akun dan peran')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="rise">Akun dan peran</h1>
        <p class="rise mt-1.5 text-sm text-ink-2 num" style="--i: 1">
            {{ $totals['mahasiswa'] ?? 0 }} mahasiswa &middot; {{ $totals['dpa'] ?? 0 }} DPA &middot; {{ $totals['validator'] ?? 0 }} validator AI &middot; {{ $totals['admin'] ?? 0 }} admin.
        </p>
    </div>
    <a href="{{ route('admin.akun.baru') }}" class="btn btn-primary"><x-icon name="plus" /> Tambah akun</a>
</div>

@error('delete')
    <div class="mt-5 rounded-xl border border-bad/40 bg-bad/10 px-4 py-3 text-sm" style="color:var(--bad);" role="alert">{{ $message }}</div>
@enderror

<form method="GET" class="mt-6 bg-surface" style="padding:1rem 1.25rem;display:flex;flex-wrap:wrap;align-items:flex-end;gap:0.85rem;">
    <div style="flex:1;min-width:200px;">
        <label for="q" class="label">Cari nama atau NIM</label>
        <input id="q" name="q" value="{{ $q }}" class="input" type="search" placeholder="Ketik nama atau NIM...">
    </div>
    <div style="min-width:165px;">
        <label for="peran" class="label">Peran</label>
        <select id="peran" name="peran" class="input">
            <option value="">Semua peran</option>
            @foreach (['mahasiswa' => 'Mahasiswa', 'dpa' => 'DPA', 'validator' => 'Validator AI', 'admin' => 'Admin'] as $v => $l)
                <option value="{{ $v }}" @selected($role === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary" type="submit"><x-icon name="magnifying-glass" /> Saring</button>
    @if ($q !== '' || $role !== '')
        <a href="{{ route('admin.akun') }}" class="btn btn-quiet">Reset</a>
    @endif
</form>

<div class="mt-6 overflow-x-auto">
    <table class="tbl min-w-[48rem]">
        <thead>
            <tr>
                <th>Nama</th>
                <th>NIM atau ID</th>
                <th>Peran</th>
                <th>DPA</th>
                <th>Status</th>
                <th><span class="sr-only">Aksi</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $u)
                <tr>
                    <td>
                        <span class="font-medium text-ink">{{ $u->name }}</span>
                        @if ($u->program_studi)
                            <span class="block text-xs text-ink-3">{{ $u->program_studi }}</span>
                        @endif
                    </td>
                    <td class="mono text-sm">{{ $u->username }}</td>
                    <td class="text-sm">{{ $u->roleLabel() }}</td>
                    <td class="text-sm text-ink-2">{{ $u->dpa?->name ?? '-' }}</td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:0.35rem;padding:0.18rem 0.6rem;border-radius:9999px;font-size:11px;font-weight:500;border:1px solid {{ $u->is_active ? 'color-mix(in srgb, var(--ok) 35%, var(--line))' : 'var(--line)' }};color:{{ $u->is_active ? 'var(--ok)' : 'var(--ink-3)' }};background:{{ $u->is_active ? 'color-mix(in srgb, var(--ok) 10%, var(--surface))' : 'var(--bg)' }};">
                            <span style="width:6px;height:6px;border-radius:9999px;background:{{ $u->is_active ? 'var(--ok)' : 'var(--ink-3)' }};"></span>
                            {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="text-right">
                        <div style="display:inline-flex;align-items:center;gap:0.45rem;flex-wrap:nowrap;">
                            <a href="{{ route('admin.akun.ubah', $u) }}" class="btn btn-quiet btn-sm"><x-icon name="pencil-simple" /> Ubah</a>
                            @if ($u->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.akun.hapus', $u) }}" onsubmit="return confirm('Hapus akun {{ addslashes($u->name) }} ({{ addslashes($u->username) }}) secara permanen? Seluruh data akademik dan riwayat risiko akun ini juga akan dihapus.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-8 text-center text-sm text-ink-2">Tidak ada akun yang cocok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('partials.pager', ['paginator' => $users])
@endsection