<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Cegah Sebelum Terlambat') | Peringatan Dini Akademik</title>
    <meta name="description" content="Sistem peringatan dini akademik untuk mahasiswa FTII UHAMKA. Ketahui risiko nilaimu sebelum nilai akhir terbit.">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (!t) t = 'dark';
                document.documentElement.dataset.theme = t;
            } catch (e) { document.documentElement.dataset.theme = 'dark'; }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* === TRANSISI HALAMAN HALUS (VIEW TRANSITIONS + GSAP + LENIS) === */
        @view-transition {
            navigation: auto;
        }
        ::view-transition-old(root) {
            animation: vtOut 160ms cubic-bezier(0.4, 0, 1, 1) both;
        }
        ::view-transition-new(root) {
            animation: vtIn 240ms cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @keyframes vtOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        @keyframes vtIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .sidebar {
            view-transition-name: app-sidebar;
        }
        ::view-transition-old(app-sidebar),
        ::view-transition-new(app-sidebar) {
            animation: none;
        }

        /* === UI/UX PRO MAX — ARCHITECTURAL DARK/LIGHT SYSTEM === */
        :root {
            --bg: #f6f7f9;
            --surface: #ffffff;
            --sunken: #eceff4;
            --ink: #0f172a;
            --ink-2: #475569;
            --ink-3: #64748b;
            --line: #e2e8f0;
            --line-strong: #cbd5e1;
            --accent: #0ea5e9;
            --accent-ink: #ffffff;
            --accent-soft: #e0f2fe;
            --ok: #10b981;
            --warn: #f59e0b;
            --bad: #ef4444;
        }

        :root[data-theme='dark'] {
            --bg: #090b10;
            --surface: #10141d;
            --sunken: #0d1017;
            --ink: #f1f5f9;
            --ink-2: #94a3b8;
            --ink-3: #64748b;
            --line: #1c2331;
            --line-strong: #2a3447;
            --accent: #38bdf8;
            --accent-ink: #090b10;
            --accent-soft: #102536;
            --ok: #34d399;
            --warn: #fbbf24;
            --bad: #f87171;
        }

        .rise { animation: none !important; }

        #page-progress {
            position: fixed;
            top: 0;
            left: 0;
            height: 2.5px;
            width: 100%;
            background: var(--accent);
            transform-origin: left center;
            transform: scaleX(0);
            opacity: 0;
            z-index: 9999;
            pointer-events: none;
            transition: transform 350ms cubic-bezier(0.22, 1, 0.36, 1), opacity 180ms ease;
        }
        #page-progress.is-loading {
            opacity: 1;
            transform: scaleX(0.78);
        }

        body {
            background-color: var(--bg);
            color: var(--ink);
        }

        .sidebar {
            background: var(--bg) !important;
            border-right: 1px solid var(--line) !important;
        }

        main .bg-surface {
            background: var(--surface) !important;
            border: 1px solid var(--line) !important;
            border-radius: 12px !important;
        }

        main .bg-surface:hover {
            border-color: var(--line-strong) !important;
        }

        .btn {
            border-radius: 8px !important;
            font-weight: 500;
            transition: background-color 140ms ease, border-color 140ms ease, opacity 140ms ease !important;
        }
        .btn:active { opacity: 0.88; }

        .btn-primary {
            background: var(--ink) !important;
            color: var(--bg) !important;
            border-color: var(--ink) !important;
        }
        .btn-primary:hover { opacity: 0.9; }

        .btn-quiet {
            background: var(--surface) !important;
            border-color: var(--line-strong) !important;
            color: var(--ink) !important;
        }
        .btn-quiet:hover {
            background: var(--sunken) !important;
            border-color: var(--ink-3) !important;
        }

        .nav-link {
            border-radius: 8px !important;
            transition: background-color 140ms ease, color 140ms ease !important;
        }
        .nav-link:hover {
            background: color-mix(in oklab, var(--ink) 5%, transparent) !important;
        }
        .nav-link[aria-current='page'],
        .nav-link.is-active {
            background: var(--surface) !important;
            color: var(--ink) !important;
            border-left: 2px solid var(--accent) !important;
        }

        .input {
            border-radius: 8px !important;
            background: var(--sunken) !important;
            border-color: var(--line-strong) !important;
        }
        .input:focus {
            outline: none !important;
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 2px color-mix(in oklab, var(--accent) 20%, transparent) !important;
        }

        .tbl tbody tr:hover { background-color: color-mix(in oklab, var(--ink) 3%, transparent); }

        .risk-chip {
            border-radius: 999px !important;
            font-weight: 600 !important;
            letter-spacing: 0.01em;
        }

        @media (prefers-reduced-motion: reduce) {
            ::view-transition-old(root),
            ::view-transition-new(root) { animation: none !important; }
        }
    </style>
</head>
<body class="min-h-dvh">
    <div id="page-progress" aria-hidden="true"></div>
    @yield('body')

    {{-- GSAP + ScrollTrigger + Lenis --}}
    <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.18/dist/lenis.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/gsap.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/ScrollTrigger.min.js" defer></script>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var hasGsap = typeof window.gsap !== 'undefined';
            var hasST = typeof window.ScrollTrigger !== 'undefined';
            var isLanding = Boolean(document.body.dataset.customMotion);
            var bar = document.getElementById('page-progress');
            var mainEl = document.querySelector('main');

            // Reset state saat kembali lewat tombol Back browser
            window.addEventListener('pageshow', function () {
                if (bar) bar.classList.remove('is-loading');
                if (mainEl && hasGsap) {
                    window.gsap.set(mainEl, { opacity: 1, y: 0, clearProps: 'transform,opacity' });
                }
            });

            if (reduceMotion) return;

            if (hasGsap && hasST) {
                window.gsap.registerPlugin(window.ScrollTrigger);
                window.ScrollTrigger.config({ ignoreMobileResize: true });
            }

            // Pasang Lenis di semua halaman (smoothWheel: false agar scroll wheel/trackpad tetap native GPU 60fps)
            if (typeof window.Lenis !== 'undefined') {
                var lenis = new window.Lenis({
                    smoothWheel: false,
                    autoRaf: true
                });
                window.__lenis = lenis;

                document.querySelectorAll('a[href^="#"]').forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        var id = link.getAttribute('href');
                        if (!id || id === '#') return;
                        var target = document.querySelector(id);
                        if (target) {
                            e.preventDefault();
                            lenis.scrollTo(target, { offset: -72, duration: 0.7 });
                        }
                    });
                });
            }

            // Transisi keluar halus dengan GSAP saat berpindah halaman internal
            document.addEventListener('click', function (e) {
                var a = e.target.closest('a[href]');
                if (!a || a.target === '_blank' || a.hasAttribute('download')) return;
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
                var href = a.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('javascript:')) return;
                if (a.origin !== window.location.origin) return;
                if (a.href === window.location.href) return;

                if (bar) bar.classList.add('is-loading');

                if (hasGsap && mainEl) {
                    e.preventDefault();
                    window.gsap.to(mainEl, {
                        opacity: 0,
                        y: -6,
                        duration: 0.16,
                        ease: 'power1.in',
                        force3D: true,
                        onComplete: function () {
                            window.location.href = a.href;
                        }
                    });
                }
            });

            // Transisi masuk bertahap (GSAP stagger) untuk seluruh halaman dasbor & non-landing
            if (hasGsap && !isLanding && mainEl) {
                var blocks = mainEl.querySelectorAll(':scope > div, :scope > section');
                if (blocks.length) {
                    window.gsap.from(blocks, {
                        opacity: 0,
                        y: 12,
                        duration: 0.36,
                        stagger: 0.05,
                        ease: 'power2.out',
                        force3D: true,
                        clearProps: 'transform,opacity'
                    });
                } else {
                    window.gsap.from(mainEl, {
                        opacity: 0,
                        y: 10,
                        duration: 0.32,
                        ease: 'power2.out',
                        force3D: true,
                        clearProps: 'transform,opacity'
                    });
                }
            }
        });
    </script>
</body>
</html>