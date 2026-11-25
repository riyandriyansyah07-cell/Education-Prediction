@extends('layouts.base')

@php
    $user = auth()->user();
    $nav = match ($user->role) {
        'mahasiswa' => [
            ['mhs.ringkasan', 'Ringkasan risiko', 'chart-line-up'],
            ['mhs.impor', 'Impor data', 'upload-simple'],
            ['mhs.riwayat', 'Riwayat semester', 'clock-counter-clockwise'],
            ['mhs.privasi', 'Privasi dan izin', 'shield-check'],
            ['mhs.catatan', 'Catatan akses', 'eye'],
            ['mhs.pengayaan', 'SKS Pengayaan', 'graduation-cap'],
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
    <div class="no-print sticky top-0 z-30 flex h-14 items-center justify-between border-b border-line bg-surface px-4 lg:hidden">
        <a href="{{ route('beranda') }}" class="font-semibold tracking-tight">Cegah Sebelum Terlambat</a>
        <button type="button" class="btn btn-quiet btn-sm" data-nav-toggle aria-expanded="false" aria-controls="sidebar">
            <x-icon name="list" /> Menu
        </button>
    </div>

    <aside id="sidebar" class="sidebar no-print fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-line bg-bg px-4 py-6 lg:z-20">
        <a href="{{ route('beranda') }}" class="flex items-center gap-3 px-2">
            <span class="inline-flex shrink-0 items-center justify-center rounded-xl border border-line-strong bg-surface shadow-sm" style="width:40px;height:40px;">
                <svg viewBox="0 0 40 40" width="26" height="26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M20 4L33 9.5V19.2C33 27.6 27.4 33.9 20 36.5C12.6 33.9 7 27.6 7 19.2V9.5L20 4Z" fill="#67b7dc" fill-opacity="0.18" stroke="#67b7dc" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M12 15.5L20 11.5L28 15.5L20 19.5L12 15.5Z" fill="#f0c46c" stroke="#f0c46c" stroke-width="1.4" stroke-linejoin="round"/>
                    <path d="M14.5 24.5L18.5 21L21.5 23.2L26 18.5" stroke="#5bc496" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="26" cy="18.5" r="2" fill="#f0c46c"/>
                </svg>
            </span>
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
                <p class="truncate font-medium">{{ $user->name }}</p>
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
        <div class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-8 lg:py-12">
            @include('partials.flash')

            @if ($user->onboarding_seen_at === null)
                <div class="no-print" style="margin-bottom: 1.5rem; padding: 1.05rem 1.3rem; border-radius: 12px; border: 1px solid color-mix(in srgb, var(--accent) 26%, var(--line)); border-left: 4px solid var(--accent); background: linear-gradient(135deg, color-mix(in srgb, var(--accent) 9%, var(--surface)) 0%, var(--surface) 100%); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
                    <div style="display: flex; align-items: flex-start; gap: 0.85rem; flex: 1; min-width: 220px;">
                        <span style="width: 34px; height: 34px; border-radius: 9px; background: color-mix(in srgb, var(--accent) 14%, transparent); color: var(--accent); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                            <x-icon name="info" />
                        </span>
                        <div style="min-width: 0;">
                            <p class="text-sm font-semibold text-ink" style="margin: 0;">Selamat datang di Cegah Sebelum Terlambat, {{ $user->name }}!</p>
                            <p class="text-xs text-ink-2" style="margin: 0.3rem 0 0; line-height: 1.55;">
                                @if ($user->isStudent())
                                    Mulai dengan mengimpor KHS (CSV, XLSX, atau PDF) di menu <strong>Impor data</strong>, pantau target UAS minimal di <strong>Ringkasan risiko</strong>, dan catat progres 20 SKS di <strong>SKS Pengayaan</strong>.
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
@endsection