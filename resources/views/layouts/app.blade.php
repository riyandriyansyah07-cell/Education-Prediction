@extends('layouts.base')

@php
    $user = auth()->user();
    $nav = match ($user->role) {
        'mahasiswa' => [
            ['mhs.ringkasan', 'Ringkasan risiko', 'chart-line-up'],
            ['mhs.impor', 'Impor data', 'upload-simple'],
            ['mhs.riwayat', 'Riwayat semester', 'clock-counter-clockwise'],
            ['mhs.privasi', 'Privasi & pengingat', 'shield-check'],
            ['mhs.pengayaan', 'Pengayaan & SKPI', 'graduation-cap'],
            ['mhs.catatan', 'Catatan akses', 'eye'],
        ],
        'dpa' => [
            ['dpa.bimbingan', 'Mahasiswa bimbingan', 'users'],
            ['dpa.riwayat', 'Riwayat bimbingan', 'clock-counter-clockwise'],
        ],
        'validator' => [
            ['validator.audit', 'Audit & Validasi AI', 'shield-check'],
        ],
        default => [
            ['admin.dasbor', 'Dasbor ringkasan', 'house'],
            ['admin.akun', 'Akun dan peran', 'identification-card'],
            ['admin.tiket', 'Tiket pengajuan akun', 'list-checks'],
            ['admin.skpi', 'Verifikasi SKPI', 'graduation-cap'],
            ['admin.historis', 'Data historis', 'database'],
            ['admin.model', 'Model prediksi', 'brain'],
            ['admin.catatan', 'Catatan akses', 'eye'],
        ],
    };

    $pendingTicketsCount = $user->isAdmin()
        ? \App\Models\AccountTicket::where('status', \App\Models\AccountTicket::STATUS_PENDING)->count()
        : 0;

    $pendingSkpiCount = ($user->isAdmin() && \Illuminate\Support\Facades\Schema::hasTable('skpi_documents'))
        ? \App\Models\SkpiDocument::where('verification_status', \App\Models\SkpiDocument::STATUS_MENUNGGU)->count()
        : 0;
@endphp

@section('body')
    {{-- Header Mobile Sticky dengan Menu di Kiri, Logo, & Toggle Tema di Kanan --}}
    <header class="no-print sticky top-0 z-30 flex h-14 items-center justify-between gap-2 border-b border-line bg-surface px-4 lg:hidden" style="backdrop-filter:blur(10px);">
        <div style="display:flex;align-items:center;gap:0.55rem;min-width:0;">
            <button type="button" class="btn btn-quiet btn-sm" data-nav-toggle aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="sidebar" style="min-height:36px;padding:0.35rem 0.55rem;flex-shrink:0;">
                <x-icon name="list" />
            </button>
            <a href="{{ route('beranda') }}" class="inline-flex items-center gap-2 font-semibold tracking-tight" style="min-width:0;">
                <x-brand-logo :size="28" />
                <span class="truncate text-sm">Cegah Sebelum Terlambat</span>
            </a>
        </div>
        <button type="button" class="btn btn-quiet btn-sm" data-theme-toggle aria-label="Ganti tema terang atau gelap" style="min-height:36px;padding:0.35rem 0.55rem;flex-shrink:0;">
            <x-icon name="sun" class="hidden dark:block" /><x-icon name="moon" class="dark:hidden" />
        </button>
    </header>

    <div id="nav-backdrop" class="app-nav-backdrop no-print" aria-hidden="true"></div>

    <aside id="sidebar" class="sidebar no-print fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-line bg-bg px-4 py-6 lg:z-20">
        <a href="{{ route('beranda') }}" class="flex items-center gap-3 px-2">
            <x-brand-logo :size="38" class="shadow-sm" />
            <span>
                <span class="block text-base font-semibold leading-tight tracking-tight">Cegah Sebelum<br>Terlambat</span>
                <span class="mt-0.5 block text-[11px] text-ink-3">FTII UHAMKA</span>
            </span>
        </a>

        <nav class="mt-8 flex flex-col gap-1" aria-label="Menu utama">
            @foreach ($nav as [$route, $label, $icon])
                <a href="{{ route($route) }}" class="nav-link" @if (request()->routeIs($route.'*')) aria-current="page" @endif>
                    <x-icon :name="$icon" />
                    <span class="truncate">{{ $label }}</span>
                    @if ($route === 'admin.tiket' && $pendingTicketsCount > 0)
                        <span class="mono" style="margin-left:auto;min-width:20px;height:20px;padding:0 6px;border-radius:9999px;font-size:11px;font-weight:700;line-height:1;display:inline-flex;align-items:center;justify-content:center;background:var(--bad);color:#ffffff;flex-shrink:0;" data-badge-tickets title="{{ $pendingTicketsCount }} tiket menunggu verifikasi">
                            {{ $pendingTicketsCount > 99 ? '99+' : $pendingTicketsCount }}
                        </span>
                    @endif
                    @if ($route === 'admin.skpi' && $pendingSkpiCount > 0)
                        <span class="mono" style="margin-left:auto;min-width:20px;height:20px;padding:0 6px;border-radius:9999px;font-size:11px;font-weight:700;line-height:1;display:inline-flex;align-items:center;justify-content:center;background:var(--bad);color:#ffffff;flex-shrink:0;" data-badge-skpi title="{{ $pendingSkpiCount }} bukti SKPI menunggu verifikasi">
                            {{ $pendingSkpiCount > 99 ? '99+' : $pendingSkpiCount }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="mt-auto border-t border-line pt-4">
            <div class="px-3">
                <p class="truncate text-sm font-semibold text-ink">{{ $user->name }}</p>
                <p class="truncate text-xs text-ink-3">{{ $user->roleLabel() }}</p>
            </div>
            <div class="mt-3 px-1" style="display:flex;flex-direction:column;gap:0.2rem;">
                <button type="button" data-notif-enable class="nav-link w-full" style="display:none;text-align:left;" aria-label="Aktifkan notifikasi browser">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 8a5 5 0 0 0-10 0c0 4-2 5.5-2 5.5h14s-2-1.5-2-5.5"/><path d="M8.5 16.5a1.6 1.6 0 0 0 3 0"/></svg>
                    <span data-notif-label class="truncate">Aktifkan notifikasi</span>
                </button>
                <a href="{{ route('sandi.edit') }}" class="nav-link {{ request()->routeIs('sandi.*') ? 'is-active' : '' }}"><x-icon name="lock-key" /> Ubah kata sandi</a>
            </div>
            <div class="mt-1 flex items-center gap-2 px-1">
                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <button class="nav-link w-full" type="submit"><x-icon name="sign-out" /> Keluar</button>
                </form>
                <button type="button" class="btn btn-quiet btn-sm" data-theme-toggle aria-label="Ganti tema terang atau gelap">
                    <x-icon name="sun" class="hidden dark:block" /><x-icon name="moon" class="dark:hidden" />
                </button>
            </div>
        </div>
    </aside>

    <main class="lg:pl-64">
        <div class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-8 lg:py-10">
            @include('partials.flash')

            @if ($user->onboarding_seen_at === null)
                <div class="no-print" style="margin-bottom: 1.5rem; padding: 1.05rem 1.3rem; border-radius: 14px; border: 1px solid color-mix(in srgb, var(--accent) 26%, var(--line)); border-left: 4px solid var(--accent); background: linear-gradient(135deg, color-mix(in srgb, var(--accent) 8%, var(--surface)) 0%, var(--surface) 100%); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
                    <div style="display: flex; align-items: flex-start; gap: 0.85rem; flex: 1; min-width: 220px;">
                        <span style="width: 34px; height: 34px; border-radius: 9px; background: color-mix(in srgb, var(--accent) 14%, transparent); color: var(--accent); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                            <x-icon name="info" />
                        </span>
                        <div style="min-width: 0;">
                            <p class="text-sm font-semibold text-ink" style="margin: 0;">Selamat datang di Cegah Sebelum Terlambat, {{ $user->name }}!</p>
                            <p class="text-xs text-ink-2" style="margin: 0.3rem 0 0; line-height: 1.55;">
                                @if ($user->isStudent())
                                    Mulai dengan mengimpor KHS (CSV, XLSX, atau PDF) di menu <strong>Impor data</strong>, pantau target UAS minimal di <strong>Ringkasan risiko</strong>, dan catat progres 20 SKS serta 4 syarat SKPI di <strong>Pengayaan &amp; SKPI</strong>.
                                @elseif ($user->isDpa())
                                    Pantau perkembangan risiko mahasiswa bimbingan Anda pada menu <strong>Mahasiswa bimbingan</strong>, lalu catat hasil konsultasi dan tindak lanjut secara berkala.
                                @elseif ($user->isValidator())
                                    Periksa kesesuaian hasil evaluasi AI Gemini terhadap perhitungan matematis pada menu <strong>Audit &amp; Validasi AI</strong>, lalu simpan verifikasi dan unduh laporan akurasi CSV.
                                @else
                                    Pantau ringkasan keseluruhan di <strong>Dasbor ringkasan</strong>, kelola akun dan <strong>Tiket pengajuan akun</strong>, unggah data historis, serta latih dan aktifkan model prediksi dari panel ini.
                                @endif
                            </p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('onboarding.dismiss') }}" style="flex-shrink: 0;">
                        @csrf
                        <button type="submit" class="btn btn-quiet btn-sm" style="white-space: nowrap;">Mengerti, sembunyikan</button>
                    </form>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <script>
        (function () {
            var toggle = document.querySelector('[data-nav-toggle]');
            var sidebar = document.getElementById('sidebar');
            var backdrop = document.getElementById('nav-backdrop');
            if (!toggle || !sidebar || !backdrop) return;

            function syncBackdrop() {
                var open = toggle.getAttribute('aria-expanded') === 'true' || sidebar.classList.contains('is-open');
                backdrop.classList.toggle('is-open', open);
            }

            toggle.addEventListener('click', function () {
                setTimeout(syncBackdrop, 10);
            });
            backdrop.addEventListener('click', function () {
                if (toggle.getAttribute('aria-expanded') === 'true' || sidebar.classList.contains('is-open')) {
                    toggle.click();
                }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && backdrop.classList.contains('is-open')) {
                    toggle.click();
                }
            });
        })();
    </script>
    <script>
        (function () {
            if (!('Notification' in window)) return;
            var btn = document.querySelector('[data-notif-enable]');
            var lbl = document.querySelector('[data-notif-label]');
            var pollUrl = @json(route('notifikasi.cek'));
            var iconUrl = @json(asset('notif-icon.png') . '?v=2');
            var SEEN_KEY = 'cst_seen_notif_keys_v1';
            var swReg = null;

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').then(function (reg) {
                    swReg = reg;
                }).catch(function () {});
            }

            function getSeen() {
                try {
                    return JSON.parse(localStorage.getItem(SEEN_KEY) || '[]');
                } catch (e) { return []; }
            }
            function markSeen(key) {
                try {
                    var arr = getSeen();
                    if (arr.indexOf(key) === -1) {
                        arr.push(key);
                        if (arr.length > 60) arr = arr.slice(-60);
                        localStorage.setItem(SEEN_KEY, JSON.stringify(arr));
                    }
                } catch (e) {}
            }

            function showOsNotif(title, body, url, tag) {
                if (Notification.permission !== 'granted') return;
                var opts = {
                    body: body,
                    icon: iconUrl,
                    badge: iconUrl,
                    tag: tag || 'cst-notif',
                    data: { url: url || '/beranda' }
                };
                if (swReg && swReg.showNotification) {
                    swReg.showNotification(title, opts).catch(function () {
                        try {
                            var n = new Notification(title, opts);
                            n.onclick = function () { window.focus(); if (url) window.location.href = url; };
                        } catch (e) {}
                    });
                } else {
                    try {
                        var n = new Notification(title, opts);
                        n.onclick = function () { window.focus(); if (url) window.location.href = url; };
                    } catch (e) {}
                }
            }

            function syncButton() {
                if (!btn || !lbl) return;
                btn.style.display = 'flex';
                if (Notification.permission === 'granted') {
                    lbl.textContent = 'Notifikasi aktif';
                    btn.style.color = 'var(--ok)';
                } else if (Notification.permission === 'denied') {
                    lbl.textContent = 'Notifikasi diblokir';
                    btn.style.color = 'var(--ink-3)';
                } else {
                    lbl.textContent = 'Aktifkan notifikasi';
                    btn.style.color = 'var(--accent)';
                }
            }

            function checkNow(seedOnly) {
                fetch(pollUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (data) {
                        if (!data || !Array.isArray(data.items)) return;

                        // Perbarui badge sidebar Admin secara live jika ada perubahan
                        var bTicket = document.querySelector('[data-badge-tickets]');
                        if (bTicket && typeof data.pending_tickets === 'number') {
                            bTicket.textContent = data.pending_tickets > 99 ? '99+' : String(data.pending_tickets);
                            bTicket.style.display = data.pending_tickets > 0 ? 'inline-flex' : 'none';
                        }
                        var bSkpi = document.querySelector('[data-badge-skpi]');
                        if (bSkpi && typeof data.pending_skpi === 'number') {
                            bSkpi.textContent = data.pending_skpi > 99 ? '99+' : String(data.pending_skpi);
                            bSkpi.style.display = data.pending_skpi > 0 ? 'inline-flex' : 'none';
                        }

                        var seen = getSeen();
                        data.items.forEach(function (item) {
                            if (seen.indexOf(item.key) === -1) {
                                markSeen(item.key);
                                if (!seedOnly && Notification.permission === 'granted') {
                                    showOsNotif(item.title, item.body, item.url, item.key);
                                }
                            }
                        });
                    })
                    .catch(function () {});
            }

            syncButton();

            if (btn) {
                btn.addEventListener('click', function () {
                    if (Notification.permission === 'granted') {
                        showOsNotif('Notifikasi Aktif', 'Kamu akan menerima pemberitahuan saat ada E-Ticket, bukti SKPI, atau pengingat baru.', window.location.href, 'cst-test');
                        return;
                    }
                    if (Notification.permission === 'denied') {
                        alert('Izin notifikasi sedang diblokir di browser. Buka pengaturan situs (ikon gembok di bilah alamat) lalu ubah Notifikasi menjadi Izinkan.');
                        return;
                    }
                    Notification.requestPermission().then(function (perm) {
                        syncButton();
                        if (perm === 'granted') {
                            showOsNotif('Notifikasi Diaktifkan', 'Notifikasi untuk Cegah Sebelum Terlambat sudah aktif di perangkat ini.', window.location.href, 'cst-enabled');
                            checkNow(false);
                        }
                    });
                });
            }

            // Langsung tampilkan notifikasi untuk tiket/SKPI baru yang belum pernah dilihat di perangkat ini
            if (Notification.permission === 'granted') {
                checkNow(false);
            }
            setInterval(function () {
                if (Notification.permission === 'granted') {
                    checkNow(false);
                }
            }, 30000);
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden && Notification.permission === 'granted') {
                    checkNow(false);
                }
            });
        })();
    </script>
@endsection