@extends('layouts.app')

@section('title', $account->exists ? 'Ubah akun' : 'Tambah akun')

@section('content')
<a href="{{ route('admin.akun') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-accent hover:underline"><x-icon name="arrow-right" class="rotate-180" size="size-4" /> Daftar akun</a>
<div class="mt-3">
    <h1>{{ $account->exists ? 'Ubah akun' : 'Tambah akun' }}</h1>
    <p class="mt-1 text-sm text-ink-2">Lengkapi identitas, peran, dan penugasan akademik pengguna.</p>
</div>

<form method="POST" action="{{ $account->exists ? route('admin.akun.update', $account) : route('admin.akun.store') }}" class="mt-6 max-w-2xl bg-surface" style="padding:1.5rem 1.65rem;display:flex;flex-direction:column;gap:1.15rem;">
    @csrf
    @if ($account->exists) @method('PUT') @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="name" class="label">Nama lengkap</label>
            <input id="name" name="name" class="input" required value="{{ old('name', $account->name) }}">
            @error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="username" class="label">NIM atau ID pegawai</label>
            <input id="username" name="username" class="input mono" required value="{{ old('username', $account->username) }}" @readonly($account->exists)>
            @if ($account->exists)
                <p class="mt-1.5 text-xs text-ink-3">Tidak bisa diubah. Kunci relasi data akademik diturunkan dari NIM ini.</p>
            @endif
            @error('username')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="role" class="label">Peran</label>
            @if ($account->exists)
                <input id="role" class="input" value="{{ $account->roleLabel() }}" readonly>
            @else
                <select id="role" name="role" class="input" data-role-select>
                    @foreach (['mahasiswa' => 'Mahasiswa', 'dpa' => 'Dosen pembimbing akademik', 'validator' => 'Validator AI', 'admin' => 'Admin sekretariat'] as $v => $l)
                        <option value="{{ $v }}" @selected(old('role', $account->role) === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            @endif
            @error('role')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="label">Email <span class="font-normal text-ink-3">(opsional)</span></label>
            <input id="email" name="email" type="email" class="input" value="{{ old('email', $account->email) }}" placeholder="nama@uhamka.ac.id">
            @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
        </div>
    </div>

    @if (! $account->exists || $account->isStudent())
        <div class="grid gap-4 sm:grid-cols-2">
            <div data-dpa-field>
                <label for="program_studi" class="label">Program studi <span class="font-normal text-ink-3">(mahasiswa)</span></label>
                <select id="program_studi" name="program_studi" class="input">
                    <option value="">Belum ditetapkan</option>
                    @foreach (\App\Models\AccountTicket::PROGRAMS as $prodi)
                        <option value="{{ $prodi }}" @selected(old('program_studi', $account->program_studi) === $prodi)>{{ $prodi }}</option>
                    @endforeach
                </select>
                @error('program_studi')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>

            <div data-dpa-field>
                <label for="dpa_id" class="label">DPA</label>
                <select id="dpa_id" name="dpa_id" class="input">
                    <option value="">Belum ditetapkan</option>
                    @foreach ($dpas as $d)
                        <option value="{{ $d->id }}" @selected((string) old('dpa_id', $account->dpa_id) === (string) $d->id)>
                            {{ $d->name }}{{ ! $d->is_active ? ' (nonaktif)' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('dpa_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
        </div>
    @endif

    <div>
        <label for="password" class="label">{{ $account->exists ? 'Kata sandi baru' : 'Kata sandi awal' }}</label>
        <input id="password" name="password" type="password" class="input" autocomplete="new-password" @required(! $account->exists)>
        <p class="mt-1.5 text-xs text-ink-3">{{ $account->exists ? 'Kosongkan bila tidak diganti. ' : '' }}Minimal 8 karakter. Disimpan sebagai hash bcrypt.</p>
        @error('password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
    </div>

    @if ($account->exists)
        <label style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.85rem 1rem;border-radius:10px;border:1px solid var(--line);background:var(--bg);cursor:pointer;">
            <input type="checkbox" name="is_active" value="1" class="mt-1 size-4 accent-[var(--accent)]" @checked(old('is_active', $account->is_active))>
            <span class="text-sm font-medium text-ink">Akun aktif<span class="block text-xs font-normal text-ink-2 mt-0.5">Akun nonaktif tidak bisa masuk. Datanya tetap tersimpan.</span></span>
        </label>
    @endif

    <div class="flex flex-wrap gap-3 pt-2 border-t border-line">
        <button type="submit" class="btn btn-primary">Simpan akun</button>
        <a href="{{ route('admin.akun') }}" class="btn btn-quiet">Batal</a>
    </div>
</form>
@endsection
