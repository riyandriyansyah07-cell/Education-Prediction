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
        ],
        'dpa' => [
            ['dpa.bimbingan', 'Mahasiswa bimbingan', 'users'],
        ],
        default => [
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
            @if (is_null($user->onboarding_seen_at))
                @php
                    $tourSteps = match ($user->role) {
                        'dpa' => [
                            ['selector' => 'a[href$="/dpa/bimbingan"]', 'title' => '1. Daftar Mahasiswa Bimbingan', 'desc' => 'Buka menu ini untuk memantau ringkasan kohor, notifikasi eskalasi risiko, dan daftar mahasiswa bimbingan yang sudah memberi izin akses.'],
                            ['selector' => 'main', 'title' => '2. Penilaian Keaktifan & Tindak Lanjut', 'desc' => 'Klik salah satu mahasiswa untuk memperbarui keaktifan bimbingan, status tugas akhir (bobot 10% risiko), serta mencatat hasil bimbingan.'],
                            ['selector' => 'a[href$="/kata-sandi"]', 'title' => '3. Ubah Kata Sandi Mandiri', 'desc' => 'Ganti kata sandi awal akun DPA kamu di menu ini agar akun tetap aman.'],
                        ],
                        'admin' => [
                            ['selector' => 'a[href$="/admin/tiket-akun"]', 'title' => '1. Tiket Pengajuan Akun', 'desc' => 'Verifikasi pengajuan akun mahasiswa/DPA atau reset sandi dari menu ini. Email notifikasi otomatis terkirim ke pemohon saat disetujui atau ditolak.'],
                            ['selector' => 'a[href$="/admin/akun"]', 'title' => '2. Akun dan Peran', 'desc' => 'Kelola seluruh akun pengguna serta pasangkan setiap mahasiswa dengan Dosen Pembimbing Akademik (DPA)-nya.'],
                            ['selector' => 'a[href$="/admin/data-historis"]', 'title' => '3. Data Historis', 'desc' => 'Unggah atau kelola dataset historis kelulusan mahasiswa sebagai data latih sistem.'],
                            ['selector' => 'a[href$="/admin/model"]', 'title' => '4. Model Prediksi', 'desc' => 'Evaluasi performa model prediksi dan kelola versi model yang digunakan.'],
                            ['selector' => 'a[href$="/admin/catatan-akses"]', 'title' => '5. Catatan Akses (Audit Log)', 'desc' => 'Pantau seluruh riwayat akses dan pembaruan data risiko mahasiswa beserta NIM-nya.'],
                        ],
                        default => [
                            ['selector' => 'a[href$="/kata-sandi"]', 'title' => '1. Ganti Kata Sandi Awal', 'desc' => 'Langkah pertama setelah login dengan sandi awal dari sekretariat: ubah kata sandimu di sini agar akun tetap aman.'],
                            ['selector' => 'a[href$="/mahasiswa/impor"]', 'title' => '2. Impor Data Akademik', 'desc' => 'Unggah KHS atau daftar nilai (CSV/Excel, atau konversi PDF KHS ke Excel) di menu Impor data untuk membangun riwayat nilaimu.'],
                            ['selector' => 'a[href$="/mahasiswa/ringkasan"]', 'title' => '3. Ringkasan Risiko & Cek Berkala', 'desc' => 'Isi nilai UTS/Tugas/UAS secara langsung di tabel Cek Berkala atau Input Cepat untuk melihat proyeksi nilai sementara (N), target UAS minimal (U_min), dan saran AI.'],
                            ['selector' => 'a[href$="/mahasiswa/riwayat"]', 'title' => '4. Riwayat Semester', 'desc' => 'Pantau perkembangan nilai mata kuliah dan tren tingkat risikomu dari semester ke semester.'],
                            ['selector' => 'a[href$="/mahasiswa/privasi"]', 'title' => '5. Atur Izin DPA & Pengingat Email', 'desc' => 'Aktifkan izin agar DPA dapat memantau risikomu, serta nyalakan pengingat otomatis ke alamat emailmu.'],
                        ],
                    };
                @endphp

                <style>
                    @keyframes tourPopIn { from { opacity: 0; transform: translateY(8px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
                    #tour-spotlight {
                        position: fixed;
                        z-index: 70;
                        border-radius: 10px;
                        border: 2px solid #22d3ee;
                        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.76), 0 0 24px rgba(34, 211, 238, 0.35);
                        pointer-events: none;
                        transition: top 320ms cubic-bezier(0.16, 1, 0.3, 1),
                                    left 320ms cubic-bezier(0.16, 1, 0.3, 1),
                                    width 320ms cubic-bezier(0.16, 1, 0.3, 1),
                                    height 320ms cubic-bezier(0.16, 1, 0.3, 1),
                                    opacity 220ms ease;
                    }
                    #tour-popover {
                        position: fixed;
                        z-index: 75;
                        width: min(360px, calc(100vw - 24px));
                        animation: tourPopIn 240ms cubic-bezier(0.16, 1, 0.3, 1);
                        transition: top 280ms cubic-bezier(0.16, 1, 0.3, 1),
                                    left 280ms cubic-bezier(0.16, 1, 0.3, 1),
                                    opacity 200ms ease;
                    }
                </style>

                <div id="tour-spotlight" aria-hidden="true"></div>

                <div id="tour-popover" class="rounded-md border border-line bg-surface p-5 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="tour-title">
                    <div class="flex items-center justify-between gap-2">
                        <span id="tour-counter" class="text-xs font-semibold uppercase tracking-wide text-accent"></span>
                        <button type="button" id="tour-skip" class="btn btn-quiet btn-sm">Lewati</button>
                    </div>
                    <h2 id="tour-title" class="mt-2 text-base font-semibold text-ink"></h2>
                    <p id="tour-desc" class="mt-1.5 text-sm leading-relaxed text-ink-2"></p>

                    <div id="tour-dots" class="mt-4 flex items-center gap-1.5"></div>

                    <div class="mt-4 flex items-center justify-between gap-2 border-t border-line pt-3">
                        <button type="button" id="tour-prev" class="btn btn-quiet btn-sm">Sebelumnya</button>
                        <button type="button" id="tour-next" class="btn btn-primary btn-sm">Lanjut</button>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const steps = @json($tourSteps);
                    const spot = document.getElementById('tour-spotlight');
                    const pop = document.getElementById('tour-popover');
                    const counter = document.getElementById('tour-counter');
                    const title = document.getElementById('tour-title');
                    const desc = document.getElementById('tour-desc');
                    const dots = document.getElementById('tour-dots');
                    const prevBtn = document.getElementById('tour-prev');
                    const nextBtn = document.getElementById('tour-next');
                    const skipBtn = document.getElementById('tour-skip');
                    const sidebar = document.getElementById('sidebar');
                    const navToggle = document.querySelector('[data-nav-toggle]');
                    if (!spot || !pop || !steps.length) return;

                    let idx = 0;

                    function ensureSidebarIfTargetInside(el) {
                        if (!el || !sidebar || !navToggle) return;
                        if (window.innerWidth < 1024 && sidebar.contains(el)) {
                            const sbRect = sidebar.getBoundingClientRect();
                            if (sbRect.left < 0) navToggle.click();
                        }
                    }

                    function positionTour() {
                        const s = steps[idx];
                        const target = document.querySelector(s.selector);
                        ensureSidebarIfTargetInside(target);

                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }

                        setTimeout(() => {
                            const r = target ? target.getBoundingClientRect() : null;
                            const pad = 6;

                            if (r && r.width > 0 && r.height > 0) {
                                const top = Math.max(8, r.top - pad);
                                const left = Math.max(8, r.left - pad);
                                const width = Math.min(r.width + pad * 2, window.innerWidth - 16);
                                const height = Math.min(r.height + pad * 2, window.innerHeight - 16);

                                spot.style.top = top + 'px';
                                spot.style.left = left + 'px';
                                spot.style.width = width + 'px';
                                spot.style.height = height + 'px';

                                const popRect = pop.getBoundingClientRect();
                                let popLeft = left + width + 16;
                                let popTop = top;

                                if (popLeft + popRect.width > window.innerWidth - 12) {
                                    popLeft = Math.max(12, Math.min(left, window.innerWidth - popRect.width - 12));
                                    popTop = top + height + 14;
                                    if (popTop + popRect.height > window.innerHeight - 12) {
                                        popTop = Math.max(12, top - popRect.height - 14);
                                    }
                                }
                                popTop = Math.max(12, Math.min(popTop, window.innerHeight - popRect.height - 12));

                                pop.style.left = popLeft + 'px';
                                pop.style.top = popTop + 'px';
                            } else {
                                spot.style.top = '50%';
                                spot.style.left = '50%';
                                spot.style.width = '0px';
                                spot.style.height = '0px';
                                const popRect = pop.getBoundingClientRect();
                                pop.style.left = Math.max(12, (window.innerWidth - popRect.width) / 2) + 'px';
                                pop.style.top = Math.max(12, (window.innerHeight - popRect.height) / 2) + 'px';
                            }
                        }, 60);
                    }

                    function render() {
                        const s = steps[idx];
                        counter.textContent = 'Alur ' + (idx + 1) + ' dari ' + steps.length;
                        title.textContent = s.title;
                        desc.textContent = s.desc;

                        dots.innerHTML = '';
                        steps.forEach((_, i) => {
                            const dot = document.createElement('span');
                            dot.style.cssText = 'height:6px;border-radius:999px;transition:all 200ms;' +
                                (i === idx ? 'width:22px;background:#22d3ee;' : 'width:6px;background:rgba(148,163,184,0.35);');
                            dots.appendChild(dot);
                        });

                        prevBtn.style.visibility = idx === 0 ? 'hidden' : 'visible';
                        nextBtn.textContent = idx === steps.length - 1 ? 'Selesai & Mulai' : 'Lanjut';

                        positionTour();
                    }

                    function closeAndSave() {
                        spot.style.opacity = '0';
                        pop.style.opacity = '0';
                        fetch(@json(route('onboarding.dismiss')), {
                            method: 'POST',
                            keepalive: true,
                            headers: {
                                'X-CSRF-TOKEN': @json(csrf_token()),
                                'Accept': 'application/json'
                            }
                        }).catch(() => {});
                        if (window.innerWidth < 1024 && sidebar && navToggle && sidebar.getBoundingClientRect().left >= 0) { navToggle.click(); }
                        setTimeout(() => { spot.remove(); pop.remove(); }, 230);
                    }

                    prevBtn.addEventListener('click', function () {
                        if (idx > 0) { idx--; render(); }
                    });
                    nextBtn.addEventListener('click', function () {
                        if (idx < steps.length - 1) { idx++; render(); }
                        else { closeAndSave(); }
                    });
                    skipBtn.addEventListener('click', closeAndSave);
                    window.addEventListener('resize', positionTour);

                    render();
                });
                </script>
            @endif
            @yield('content')
        </div>
    </main>
@endsection