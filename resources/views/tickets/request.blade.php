@extends('layouts.base')

@section('title', 'Ajukan E-Ticket Akun')

@section('body')
<header class="sticky top-0 z-30 border-b border-line bg-bg/90 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4 sm:px-8">
        <a href="{{ route('landing') }}" class="inline-flex items-center gap-2.5 font-semibold tracking-tight">
            <x-brand-logo :size="28" />
            <span>Cegah Sebelum Terlambat</span>
        </a>
        <div style="display:flex;align-items:center;gap:0.5rem;">
            <button type="button" class="btn btn-quiet btn-sm" data-theme-toggle aria-label="Ganti tema terang atau gelap">
                <x-icon name="sun" class="hidden dark:block" /><x-icon name="moon" class="dark:hidden" />
            </button>
            <a href="{{ route('login') }}" class="btn btn-quiet btn-sm">Kembali ke Masuk</a>
        </div>
    </div>
</header>

<main class="mx-auto max-w-5xl px-4 py-8 sm:px-8 lg:py-10">
    <div>
        <h1 class="rise">E-Ticket Pengajuan Akun</h1>
        <p class="rise mt-1.5 max-w-[62ch] text-sm text-ink-2" style="--i: 1">
            Ajukan pembuatan akun atau reset kata sandi ke Admin Sekretariat FTII. Setelah disetujui, tautan aman untuk mengatur kata sandi dikirim ke email aktifmu.
        </p>
    </div>

    @if (session('status'))
        <div role="status" class="mt-6 rounded-xl border border-ok/40 bg-ok/10 px-4 py-3.5 text-ink">
            <div class="flex items-start gap-3">
                <x-icon name="check-circle" class="mt-0.5 shrink-0 text-ok" />
                <div style="min-width:0;flex:1;">
                    <p class="text-sm">{{ session('status') }}</p>
                    @if (session('created_ticket_code'))
                        <div style="margin-top:0.75rem;display:flex;flex-wrap:wrap;align-items:center;gap:0.55rem;">
                            <code id="created-ticket-code" class="mono" style="padding:0.32rem 0.7rem;border-radius:8px;background:var(--surface);border:1px solid var(--line-strong);font-size:0.86rem;font-weight:700;letter-spacing:0.02em;">{{ session('created_ticket_code') }}</code>
                            <button type="button" id="copy-created-ticket" class="btn btn-primary btn-sm">Salin Kode Tiket</button>
                            <button type="button" id="fill-created-ticket" class="btn btn-quiet btn-sm">Isi ke Cek Status &rarr;</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Banner otomatis dari localStorage perangkat jika user klik Kembali atau refresh --}}
    <div id="saved-ticket-banner" style="display:none;margin-top:1.25rem;padding:0.9rem 1.15rem;border-radius:12px;border:1px solid color-mix(in srgb, var(--accent) 32%, var(--line));background:color-mix(in srgb, var(--accent) 8%, var(--surface));align-items:center;justify-content:space-between;gap:0.75rem;flex-wrap:wrap;">
        <div style="min-width:0;flex:1;">
            <p class="text-xs font-semibold text-ink" style="margin:0;">Kode E-Ticket terakhir yang dibuat di perangkat ini:</p>
            <p class="mt-1 text-xs text-ink-2" style="margin:0.2rem 0 0;">
                <code id="saved-ticket-code" class="mono font-semibold text-ink"></code>
                <span id="saved-ticket-verif-wrap" class="ml-1 text-ink-3"></span>
            </p>
        </div>
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.45rem;flex-shrink:0;">
            <button type="button" id="copy-saved-ticket" class="btn btn-quiet btn-sm">Salin Kode</button>
            <button type="button" id="fill-saved-ticket" class="btn btn-primary btn-sm">Cek Status</button>
        </div>
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-12" style="align-items:start;">
        <section class="lg:col-span-7" aria-labelledby="form-tiket">
            <h2 id="form-tiket" class="text-lg font-semibold">Buat Tiket Baru</h2>
            <form method="POST" action="{{ route('tiket.submit') }}" class="mt-4 space-y-4 bg-surface" style="padding:1.4rem 1.5rem;">
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
                    <label for="email" class="label">Email aktif <span style="color:var(--bad);">*</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@uhamka.ac.id" class="input" required>
                    <p class="mt-1.5 text-xs text-ink-3">Wajib diisi — tautan pengaturan kata sandi sekali pakai akan dikirim ke email ini setelah disetujui Admin.</p>
                    @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="notes" class="label">Keterangan tambahan <span class="font-normal text-ink-3">(Nama DPA / Catatan)</span></label>
                    <textarea id="notes" name="notes" rows="3" class="input" placeholder="Contoh: DPA Erizal, S.Kom., M.Kom.">{{ old('notes') }}</textarea>
                    @error('notes')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn btn-primary w-full">Kirim Pengajuan E-Ticket</button>
            </form>
        </section>

        <aside class="lg:col-span-5" aria-labelledby="cek-tiket">
            <h2 id="cek-tiket" class="text-lg font-semibold">Cek Status E-Ticket</h2>
            <form method="GET" action="{{ route('tiket.form') }}" id="cek-tiket-form" class="mt-4 space-y-3.5 bg-surface" style="padding:1.35rem 1.4rem;">
                <div>
                    <label for="cek-kode" class="label text-xs">Kode tiket</label>
                    <input id="cek-kode" name="kode" type="text" value="{{ $searchCode }}" placeholder="TKT-20261125-XXXXXXXXXX" class="input mono uppercase" required>
                </div>
                <div>
                    <label for="cek-verifikasi" class="label text-xs">NIM / ID atau Email terdaftar</label>
                    <input id="cek-verifikasi" name="verifikasi" type="text" value="{{ $searchVerification ?? '' }}" placeholder="2303045009 atau nama@uhamka.ac.id" class="input" required>
                </div>
                <button type="submit" class="btn btn-quiet w-full">Cek Status Tiket</button>
            </form>

            @if ($lookupAttempted ?? false)
                <div class="mt-5 bg-surface" style="padding:1.25rem 1.4rem;">
                    @if (! $checkedTicket)
                        <p class="text-sm text-bad">{{ $lookupError ?? 'Tiket tidak ditemukan atau NIM/Email tidak cocok.' }}</p>
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
                        <dl class="mt-4 space-y-2.5 text-sm">
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
                            <p class="mt-4 text-xs text-ink-2">Periksa kotak masuk emailmu untuk tautan pengaturan kata sandi sekali pakai, lalu masuk ke aplikasi.</p>
                            <a href="{{ route('login') }}" class="btn btn-primary btn-sm mt-3 w-full">Masuk ke Akun Sekarang</a>
                        @endif
                    @endif
                </div>
            @else
                <p class="mt-4 text-sm text-ink-2">Masukkan kode tiket beserta NIM/ID atau email terdaftar untuk memeriksa apakah pengajuanmu sudah diproses sekretariat.</p>
            @endif
        </aside>
    </div>
</main>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var t = document.getElementById("request_type");
    var g = document.getElementById("prodi-group");
    var p = document.getElementById("program_studi");
    if (t && g && p) {
        function syncProdi() {
            var m = t.value === "akun_mahasiswa";
            g.style.display = m ? "" : "none";
            p.required = m;
            if (!m) p.value = "";
        }
        t.addEventListener("change", syncProdi);
        syncProdi();
    }

    var KEY_CODE = "cst_last_ticket_code";
    var KEY_VERIF = "cst_last_ticket_verif";
    var createdCode = @json(session('created_ticket_code'));
    var createdVerif = @json(session('created_ticket_verif'));

    if (createdCode) {
        try {
            localStorage.setItem(KEY_CODE, createdCode);
            if (createdVerif) localStorage.setItem(KEY_VERIF, createdVerif);
        } catch (e) {}
    }

    function copyText(text, btn) {
        if (!text) return;
        var done = function () {
            if (!btn) return;
            var orig = btn.textContent;
            btn.textContent = "Tersalin!";
            setTimeout(function () { btn.textContent = orig; }, 1600);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done).catch(function () {
                try {
                    var ta = document.createElement("textarea");
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand("copy");
                    document.body.removeChild(ta);
                    done();
                } catch (e) {}
            });
        }
    }

    function fillLookup(code, verif, autoSubmit) {
        var inpCode = document.getElementById("cek-kode");
        var inpVerif = document.getElementById("cek-verifikasi");
        var form = document.getElementById("cek-tiket-form");
        if (inpCode && code) inpCode.value = code;
        if (inpVerif && verif) inpVerif.value = verif;
        if (autoSubmit && form && code && verif) {
            form.submit();
        } else if (inpCode) {
            inpCode.scrollIntoView({ behavior: "smooth", block: "center" });
        }
    }

    var copyCreatedBtn = document.getElementById("copy-created-ticket");
    if (copyCreatedBtn) {
        copyCreatedBtn.addEventListener("click", function () { copyText(createdCode, copyCreatedBtn); });
    }
    var fillCreatedBtn = document.getElementById("fill-created-ticket");
    if (fillCreatedBtn) {
        fillCreatedBtn.addEventListener("click", function () { fillLookup(createdCode, createdVerif, true); });
    }

    // Jika tidak baru saja membuat tiket, tampilkan kode tersimpan di perangkat ini
    if (!createdCode) {
        try {
            var savedCode = localStorage.getItem(KEY_CODE);
            var savedVerif = localStorage.getItem(KEY_VERIF) || "";
            if (savedCode) {
                var banner = document.getElementById("saved-ticket-banner");
                var codeEl = document.getElementById("saved-ticket-code");
                var verifEl = document.getElementById("saved-ticket-verif-wrap");
                if (banner && codeEl) {
                    codeEl.textContent = savedCode;
                    if (verifEl && savedVerif) verifEl.textContent = "(" + savedVerif + ")";
                    banner.style.display = "flex";
                }
                var copySavedBtn = document.getElementById("copy-saved-ticket");
                if (copySavedBtn) {
                    copySavedBtn.addEventListener("click", function () { copyText(savedCode, copySavedBtn); });
                }
                var fillSavedBtn = document.getElementById("fill-saved-ticket");
                if (fillSavedBtn) {
                    fillSavedBtn.addEventListener("click", function () { fillLookup(savedCode, savedVerif, true); });
                }
            }
        } catch (e) {}
    }
});
</script>
@endsection