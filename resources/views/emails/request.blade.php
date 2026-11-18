@extends('layouts.base')

@section('title', 'Ajukan E-Ticket Akun')

@section('body')
<header class="sticky top-0 z-30 border-b border-line bg-bg/90 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4 sm:px-8">
        <a href="{{ route('landing') }}" class="font-semibold tracking-tight">Cegah Sebelum Terlambat</a>
        <a href="{{ route('login') }}" class="btn btn-quiet btn-sm">Kembali ke Masuk</a>
    </div>
</header>

<main class="mx-auto max-w-5xl px-4 py-10 sm:px-8">
    <h1 class="rise text-3xl sm:text-4xl">E-Ticket Pengajuan Akun</h1>
    <p class="rise mt-2 max-w-[62ch] text-ink-2" style="--i: 1">
        Belum memiliki akun atau lupa kata sandi? Kirim tiket pengajuan ke Admin Sekretariat FTII dan pantau statusnya dengan kode tiketmu.
    </p>

    @if (session('status'))
        <div role="status" class="mt-6 flex items-start gap-3 rounded-md border border-ok/40 bg-ok/10 px-4 py-3 text-ink">
            <x-icon name="check-circle" class="mt-0.5 text-ok" />
            <p>{{ session('status') }}</p>
        </div>
    @endif

    <div class="mt-10 grid gap-12 lg:grid-cols-12">
        <section class="lg:col-span-7" aria-labelledby="form-tiket">
            <h2 id="form-tiket" class="text-xl">Buat Tiket Baru</h2>
            <form method="POST" action="{{ route('tiket.submit') }}" class="mt-5 space-y-5 rounded-md border border-line bg-surface p-6">
                @csrf
                <div>
                    <label for="request_type" class="label">Jenis pengajuan</label>
                    <select id="request_type" name="request_type" class="input" required>
                        <option value="akun_mahasiswa" @selected(old('request_type') === 'akun_mahasiswa')>Pembuatan Akun Baru Mahasiswa</option>
                        <option value="akun_dpa" @selected(old('request_type') === 'akun_dpa')>Pembuatan Akun Baru DPA</option>
                        <option value="reset_sandi" @selected(old('request_type') === 'reset_sandi')>Reset Kata Sandi (Lupa Password)</option>
                    </select>
                    @error('request_type')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="label">Nama lengkap</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Contoh: Riyan Adriyansyah" class="input" required>
                        @error('name')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="username" class="label">NIM atau ID pegawai</label>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Contoh: 2303045009" class="input mono" required>
                        @error('username')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div id="prodi-group">
                    <label for="program_studi" class="label">Program studi</label>
                    <select id="program_studi" name="program_studi" class="input" required>
                        <option value="" disabled @selected(! old('program_studi'))>Pilih program studi...</option>
                        @foreach (\App\Models\AccountTicket::PROGRAMS as $prodi)
                            <option value="{{ $prodi }}" @selected(old('program_studi') === $prodi)>{{ $prodi }}</option>
                        @endforeach
                    </select>
                    @error('program_studi')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="label">Email aktif <span class="font-normal text-ink-3">(opsional)</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@uhamka.ac.id" class="input">
                    @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="notes" class="label">Keterangan tambahan <span class="font-normal text-ink-3">(Angkatan / Nama DPA)</span></label>
                    <textarea id="notes" name="notes" rows="3" class="input py-2" placeholder="Contoh: Angkatan 2023, DPA Dr. Hanafi Rahmat">{{ old('notes') }}</textarea>
                    @error('notes')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn btn-primary w-full">Kirim Pengajuan E-Ticket</button>
            </form>
        </section>

        <aside class="lg:col-span-5" aria-labelledby="cek-tiket">
            <h2 id="cek-tiket" class="text-xl">Cek Status E-Ticket</h2>
            <form method="GET" action="{{ route('tiket.form') }}" class="mt-5 flex gap-2">
                <input name="kode" type="text" value="{{ $searchCode }}" placeholder="TKT-20261116-XXXX" class="input mono flex-1 uppercase" required>
                <button type="submit" class="btn btn-quiet">Cek</button>
            </form>

            @if ($searchCode !== '')
                <div class="mt-6 rounded-md border border-line bg-surface p-5">
                    @if (! $checkedTicket)
                        <p class="text-sm text-bad">Kode tiket <span class="mono font-medium">{{ $searchCode }}</span> tidak ditemukan.</p>
                    @else
                        <div class="flex items-center justify-between gap-2">
                            <span class="mono text-sm font-semibold">{{ $checkedTicket->ticket_code }}</span>
                            @if ($checkedTicket->status === 'disetujui')
                                <span class="risk risk-chip risk-rendah">Disetujui</span>
                            @elseif ($checkedTicket->status === 'ditolak')
                                <span class="risk risk-chip risk-tinggi">Ditolak</span>
                            @else
                                <span class="risk risk-chip risk-sedang">Menunggu</span>
                            @endif
                        </div>
                        <dl class="mt-4 space-y-2 text-sm">
                            <div>
                                <dt class="text-xs text-ink-3">Pemohon</dt>
                                <dd class="font-medium">{{ $checkedTicket->name }} (<span class="mono">{{ $checkedTicket->username }}</span>)</dd>
                            </div>
                            @if ($checkedTicket->program_studi)
                                <div>
                                    <dt class="text-xs text-ink-3">Program Studi</dt>
                                    <dd>{{ $checkedTicket->program_studi }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-xs text-ink-3">Jenis Pengajuan</dt>
                                <dd>{{ $checkedTicket->typeLabel() }}</dd>
                            </div>
                            @if ($checkedTicket->admin_note)
                                <div>
                                    <dt class="text-xs text-ink-3">Catatan Sekretariat</dt>
                                    <dd class="text-ink-2">{{ $checkedTicket->admin_note }}</dd>
                                </div>
                            @endif
                        </dl>
                        @if ($checkedTicket->status === 'disetujui')
                            <a href="{{ route('login') }}" class="btn btn-primary btn-sm mt-5 w-full">Masuk ke Akun Sekarang</a>
                        @endif
                    @endif
                </div>
            @else
                <p class="mt-4 text-sm text-ink-2">Masukkan kode tiket yang kamu dapatkan setelah mengirim formulir untuk melihat apakah akunmu sudah diaktifkan oleh sekretariat.</p>
            @endif
        </aside>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('request_type');
    const prodiGroup = document.getElementById('prodi-group');
    const prodiSelect = document.getElementById('program_studi');
    if (!typeSelect || !prodiGroup || !prodiSelect) return;

    function syncProdi() {
        const isStudent = typeSelect.value === 'akun_mahasiswa';
        prodiGroup.style.display = isStudent ? '' : 'none';
        prodiSelect.required = isStudent;
        if (!isStudent) prodiSelect.value = '';
    }

    typeSelect.addEventListener('change', syncProdi);
    syncProdi();
});
</script>
@endsection