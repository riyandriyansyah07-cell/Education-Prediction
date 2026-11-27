@extends('layouts.base')

@section('title', 'Ajukan E-Ticket Akun')

@section('body')
<style>
    .tkt-header {
        position: sticky;
        top: 0;
        z-index: 30;
        border-bottom: 1px solid var(--line);
        background: color-mix(in srgb, var(--surface) 92%, transparent);
        backdrop-filter: blur(10px);
    }
    .tkt-eyebrow {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--accent);
        letter-spacing: 0.01em;
        margin-bottom: 0.35rem;
    }
    .tkt-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 20px;
        padding: 1.65rem 1.75rem;
        box-shadow: var(--shadow-card);
    }
    .tkt-pill-primary {
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        width: 100%;
        min-height: 48px;
        padding: 0.45rem 0.5rem 0.45rem 1.35rem;
        border-radius: 9999px !important;
        background: var(--ink) !important;
        color: var(--bg) !important;
        border: 1px solid var(--ink) !important;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 140ms ease, transform 100ms ease;
    }
    .tkt-pill-primary:hover { opacity: 0.92; }
    .tkt-pill-primary:active { transform: translateY(0.5px); }
    .tkt-pill-arrow {
        width: 34px;
        height: 34px;
        border-radius: 9999px;
        background: var(--surface);
        color: var(--ink);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .tkt-pill-soft {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        min-height: 44px;
        padding: 0.55rem 1.15rem;
        border-radius: 9999px !important;
        background: var(--accent-soft) !important;
        color: var(--accent) !important;
        border: 1px solid color-mix(in srgb, var(--accent) 22%, var(--line)) !important;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 140ms ease, border-color 140ms ease;
    }
    .tkt-pill-soft:hover {
        background: color-mix(in srgb, var(--accent) 14%, var(--surface)) !important;
        border-color: color-mix(in srgb, var(--accent) 36%, var(--line)) !important;
    }
    @media (max-width: 640px) {
        .tkt-card { padding: 1.25rem; }
    }
</style>

<header class="tkt-header">
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-4 sm:px-8">
        <a href="{{ route('landing') }}" class="inline-flex items-center gap-2.5 font-semibold tracking-tight" style="min-width:0;">
            <x-brand-logo :size="30" />
            <span class="truncate text-sm sm:text-base">Cegah Sebelum Terlambat</span>
        </a>
        <div style="display:flex;align-items:center;gap:0.5rem;flex-shrink:0;">
            <button type="button" class="btn btn-quiet btn-sm" data-theme-toggle aria-label="Ganti tema terang atau gelap" style="border-radius:9999px !important;padding:0.35rem 0.6rem;">
                <x-icon name="sun" class="hidden dark:block" /><x-icon name="moon" class="dark:hidden" />
            </button>
            <a href="{{ route('login') }}" class="btn btn-quiet btn-sm" style="border-radius:9999px !important;padding:0.42rem 0.95rem;">&larr; Kembali ke Masuk</a>
        </div>
    </div>
</header>

<main class="mx-auto max-w-5xl px-4 py-8 sm:px-8 lg:py-10">
    <div>
        <p class="tkt-eyebrow">Layanan Akun Sekretariat FTII</p>
        <h1 class="rise">E-Ticket Pengajuan Akun</h1>
        <p class="rise mt-1.5 max-w-[62ch] text-sm text-ink-2" style="--i: 1">
            Ajukan pembuatan akun atau reset kata sandi ke Admin Sekretariat FTII. Setelah disetujui, tautan aman untuk mengatur kata sandi dikirim ke email aktifmu.
        </p>
    </div>

    @if (session('status'))
        <div role="status" style="margin-top:1.5rem;padding:1.2rem 1.35rem;border-radius:16px;border:1px solid color-mix(in srgb, var(--ok) 35%, var(--line));border-left:4px solid var(--ok);background:var(--surface);box-shadow:var(--shadow-card);">
            <div style="display:flex;align-items:flex-start;gap:0.9rem;">
                <span style="width:32px;height:32px;border-radius:9px;background:color-mix(in srgb, var(--ok) 14%, transparent);color:var(--ok);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                    <x-icon name="check-circle" />
                </span>
                <div style="min-width:0;flex:1;">
                    <p class="text-sm font-semibold text-ink" style="margin:0;">{{ session('created_ticket_code') ? 'E-Ticket Berhasil Dibuat' : 'Informasi E-Ticket' }}</p>
                    <p class="text-sm text-ink-2" style="margin:0.35rem 0 0;line-height:1.55;">{{ session('status') }}</p>
                </div>
            </div>

            @if (session('created_ticket_code'))
                <div style="margin-top:1rem;padding:0.85rem 1rem;border-radius:12px;background:var(--sunken);border:1px solid var(--line);display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.75rem;">
                    <div style="min-width:0;">
                        <span class="text-xs text-ink-3" style="display:block;margin-bottom:0.15rem;">Kode Tiket Kamu</span>
                        <code id="created-ticket-code" class="mono text-ink" style="font-size:0.95rem;font-weight:700;letter-spacing:0.03em;word-break:break-all;">{{ session('created_ticket_code') }}</code>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem;">
                        <button type="button" id="copy-created-ticket" class="btn btn-primary btn-sm" style="border-radius:9999px !important;">Salin Kode</button>
                        <button type="button" id="fill-created-ticket" class="btn btn-quiet btn-sm" style="border-radius:9999px !important;">Cek Status &rarr;</button>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Banner otomatis dari localStorage perangkat jika user klik Kembali atau refresh --}}
    <div id="saved-ticket-banner" style="display:none;margin-top:1.5rem;padding:1rem 1.25rem;border-radius:16px;border:1px solid color-mix(in srgb, var(--accent) 30%, var(--line));border-left:4px solid var(--accent);background:var(--surface);box-shadow:var(--shadow-card);align-items:center;justify-content:space-between;gap:0.9rem;flex-wrap:wrap;">
        <div style="min-width:0;flex:1;">
            <p class="text-xs text-ink-3" style="margin:0;">E-Ticket terakhir di perangkat ini</p>
            <p style="margin:0.25rem 0 0;display:flex;flex-wrap:wrap;align-items:center;gap:0.45rem;">
                <code id="saved-ticket-code" class="mono text-sm font-semibold text-ink" style="letter-spacing:0.02em;"></code>
                <span id="saved-ticket-verif-wrap" class="text-xs text-ink-3"></span>
            </p>
        </div>
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem;flex-shrink:0;">
            <button type="button" id="copy-saved-ticket" class="btn btn-quiet btn-sm" style="border-radius:9999px !important;">Salin Kode</button>
            <button type="button" id="fill-saved-ticket" class="btn btn-primary btn-sm" style="border-radius:9999px !important;">Cek Status &rarr;</button>
            <button type="button" id="dismiss-saved-ticket" class="btn btn-quiet btn-sm" style="border-radius:9999px !important;" aria-label="Hapus pengingat tiket" title="Tutup &amp; hapus dari perangkat">&times;</button>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-12" style="align-items:start;">
        <section class="tkt-card lg:col-span-7" aria-labelledby="form-tiket">
            <h2 id="form-tiket" class="text-lg font-semibold text-ink">Buat Tiket Baru</h2>
            <p class="mt-1 text-xs text-ink-3">Data akan masuk langsung ke antrean verifikasi Admin Sekretariat FTII.</p>

            <form method="POST" action="{{ route('tiket.submit') }}" class="mt-5 space-y-4">
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

                <div style="padding-top:0.35rem;">
                    <button type="submit" class="tkt-pill-primary">
                        <span>Kirim Pengajuan E-Ticket</span>
                        <span class="tkt-pill-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 14L14 6"/><path d="M7 6h7v7"/></svg>
                        </span>
                    </button>
                </div>
            </form>
        </section>

        <aside class="lg:col-span-5" aria-labelledby="cek-tiket">
            <div class="tkt-card">
                <h2 id="cek-tiket" class="text-lg font-semibold text-ink">Cek Status E-Ticket</h2>
                <p class="mt-1 text-xs text-ink-3" style="line-height:1.55;">
                    Masukkan kode tiket beserta NIM/ID atau email terdaftar untuk memeriksa apakah pengajuanmu sudah diproses sekretariat.
                </p>

                <form method="GET" action="{{ route('tiket.form') }}" id="cek-tiket-form" class="mt-5" style="display:flex;flex-direction:column;gap:1rem;">
                    <div>
                        <label for="cek-kode" class="label text-xs">Kode tiket</label>
                        <input id="cek-kode" name="kode" type="text" value="{{ $searchCode }}" placeholder="TKT-20261125-XXXXXXXXXX" class="input mono uppercase" required>
                    </div>
                    <div>
                        <label for="cek-verifikasi" class="label text-xs">NIM / ID atau Email terdaftar</label>
                        <input id="cek-verifikasi" name="verifikasi" type="text" value="{{ $searchVerification ?? '' }}" placeholder="2303045009 atau nama@uhamka.ac.id" class="input" required>
                    </div>
                    <button type="submit" class="tkt-pill-soft">
                        <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="M13.5 13.5L17 17"/></svg>
                        <span>Cek Status Tiket</span>
                    </button>
                </form>
            </div>

            @if ($lookupAttempted ?? false)
                <div class="tkt-card mt-5">
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
                        <dl class="mt-4 text-sm" style="display:flex;flex-direction:column;gap:0.7rem;">
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
                            <a href="{{ route('login') }}" class="btn btn-primary btn-sm mt-3 w-full" style="border-radius:9999px !important;">Masuk ke Akun Sekarang</a>
                        @endif
                    @endif
                </div>
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

    var checkedCode = @json($checkedTicket?->ticket_code);
    var checkedStatus = @json($checkedTicket?->status);
    var searchedCode = @json($searchCode ?? '');
    var lookupFailed = @json(($lookupAttempted ?? false) && ! $checkedTicket);

    // 1. Otomatis hapus dari perangkat jika tiket sudah selesai (disetujui/ditolak) atau sudah dihapus Admin
    try {
        var currentSaved = localStorage.getItem(KEY_CODE);
        if (currentSaved) {
            var isDoneTicket = checkedCode && checkedCode === currentSaved && (checkedStatus === "disetujui" || checkedStatus === "ditolak");
            var isDeletedTicket = lookupFailed && searchedCode && searchedCode === currentSaved;
            if (isDoneTicket || isDeletedTicket) {
                localStorage.removeItem(KEY_CODE);
                localStorage.removeItem(KEY_VERIF);
                currentSaved = null;
            }
        }

        // 2. Hanya tampilkan banner jika bukan baru membuat tiket DAN tidak sedang membuka hasil cek tiket yang sama
        var viewingSameTicket = checkedCode && currentSaved && checkedCode === currentSaved;
        if (!createdCode && currentSaved && !viewingSameTicket) {
            var savedVerif = localStorage.getItem(KEY_VERIF) || "";
            var banner = document.getElementById("saved-ticket-banner");
            var codeEl = document.getElementById("saved-ticket-code");
            var verifEl = document.getElementById("saved-ticket-verif-wrap");
            if (banner && codeEl) {
                codeEl.textContent = currentSaved;
                if (verifEl && savedVerif) verifEl.textContent = "(" + savedVerif + ")";
                banner.style.display = "flex";
            }
            var copySavedBtn = document.getElementById("copy-saved-ticket");
            if (copySavedBtn) {
                copySavedBtn.addEventListener("click", function () { copyText(currentSaved, copySavedBtn); });
            }
            var fillSavedBtn = document.getElementById("fill-saved-ticket");
            if (fillSavedBtn) {
                fillSavedBtn.addEventListener("click", function () { fillLookup(currentSaved, savedVerif, true); });
            }
            var dismissSavedBtn = document.getElementById("dismiss-saved-ticket");
            if (dismissSavedBtn) {
                dismissSavedBtn.addEventListener("click", function () {
                    try {
                        localStorage.removeItem(KEY_CODE);
                        localStorage.removeItem(KEY_VERIF);
                    } catch (e) {}
                    if (banner) banner.style.display = "none";
                });
            }
        }
    } catch (e) {}
});
</script>
@endsection