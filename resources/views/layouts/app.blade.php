@extends('layouts.base')

@php
    $user = auth()->user();
    $nav = match ($user->role) {
        'mahasiswa' => [
            ['mhs.ringkasan', 'Ringkasan risiko', 'chart-line-up'],
            ['mhs.impor', 'Impor data', 'upload-simple'],
            ['mhs.riwayat', 'Riwayat semester', 'clock-counter-clockwise'],
            ['mhs.privasi', 'Privasi dan izin', 'shield-check'],
            ['mhs.pengayaan', 'Pengayaan & SKPI', 'graduation-cap'],
            ['mhs.catatan', 'Catatan akses', 'eye'],
        ],
        'dpa' => [
            ['dpa.bimbingan', 'Mahasiswa bimbingan', 'users'],
        ],
        'validator' => [
            ['validator.audit', 'Audit & Validasi AI', 'shield-check'],
        ],
        default => [
            ['admin.dasbor', 'Dasbor ringkasan', 'house'],
            ['admin.akun', 'Akun dan peran', 'identification-card'],
            ['admin.tiket', 'Tiket pengajuan akun', 'list-checks'],
            ['admin.historis', 'Data historis', 'database'],
            ['admin.model', 'Model prediksi', 'brain'],
            ['admin.catatan', 'Catatan akses', 'eye'],
        ],
    };
@endphp

@section('body')
    {{-- Header Mobile Sticky dengan Logo, Toggle Tema & Menu --}}
    <header class="no-print sticky top-0 z-30 flex h-14 items-center justify-between border-b border-line bg-surface px-4 lg:hidden" style="backdrop-filter:blur(10px);">
        <a href="{{ route('beranda') }}" class="inline-flex items-center gap-2.5 font-semibold tracking-tight" style="min-width:0;">
            <x-brand-logo :size="28" />
            <span class="truncate text-sm">Cegah Sebelum Terlambat</span>
        </a>
        <div style="display:flex;align-items:center;gap:0.45rem;flex-shrink:0;">
            <button type="button" class="btn btn-quiet btn-sm" data-theme-toggle aria-label="Ganti tema terang atau gelap" style="min-height:36px;padding:0.35rem 0.55rem;">
                <x-icon name="sun" class="hidden dark:block" /><x-icon name="moon" class="dark:hidden" />
            </button>
            <button type="button" class="btn btn-quiet btn-sm" data-nav-toggle aria-expanded="false" aria-controls="sidebar" style="min-height:36px;padding:0.35rem 0.7rem;">
                <x-icon name="list" /> Menu
            </button>
        </div>
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
                    <x-icon :name="$icon" /> {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="mt-auto border-t border-line pt-4">
            <div class="px-3">
                <p class="truncate text-sm font-semibold text-ink">{{ $user->name }}</p>
                <p class="truncate text-xs text-ink-3">{{ $user->roleLabel() }}</p>
            </div>
            <div class="mt-3 px-1">
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
                                    Pantau mahasiswa bimbingan yang telah memberikan izin akses pada menu <strong>Mahasiswa bimbingan</strong>, lalu catat hasil konsultasi dan tindak lanjut secara berkala.
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
@endsection