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

        html.lenis, html.lenis body { height: auto; }
        .lenis.lenis-smooth { scroll-behavior: auto !important; }
        .lenis.lenis-smooth [data-lenis-prevent] { overscroll-behavior: contain; }
        .lenis.lenis-stopped { overflow: hidden; }

        body {
            background-color: var(--bg);
            color: var(--ink);
        }

        .sidebar {
            background: var(--bg) !important;
            border-right: 1px solid var(--line) !important;
        }

        /* Penting: jangan pasang transition: transform di sini agar tidak bentrok dengan GSAP */
        main .bg-surface {
            background: var(--surface) !important;
            border: 1px solid var(--line) !important;
            border-radius: 12px !important;
            box-shadow: 0 2px 12px -4px rgba(0, 0, 0, 0.14);
            transition: border-color 150ms ease, box-shadow 150ms ease;
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
            transition: border-color 150ms ease, box-shadow 150ms ease !important;
        }
        .input:focus {
            outline: none !important;
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 3px color-mix(in oklab, var(--accent) 18%, transparent) !important;
        }

        .tbl tbody tr { transition: background-color 120ms ease; }
        .tbl tbody tr:hover { background-color: color-mix(in oklab, var(--ink) 3%, transparent); }

        .risk-chip {
            border-radius: 999px !important;
            font-weight: 600 !important;
            letter-spacing: 0.01em;
        }
    </style>
</head>
<body class="min-h-dvh">
    @yield('body')

    {{-- GSAP + ScrollTrigger + Lenis (ringan & 60fps untuk laptop) --}}
    <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.18/dist/lenis.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/gsap.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/ScrollTrigger.min.js" defer></script>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduceMotion) return;

            var hasGsap = typeof window.gsap !== 'undefined';
            var hasST = typeof window.ScrollTrigger !== 'undefined';
            if (hasGsap && hasST) {
                window.gsap.registerPlugin(window.ScrollTrigger);
            }

            // Lenis ringan dengan lerp responsif (tidak berat / tidak laggy di trackpad maupun mouse)
            if (typeof window.Lenis !== 'undefined') {
                var lenis = new window.Lenis({
                    lerp: 0.14,
                    wheelMultiplier: 0.95,
                    smoothWheel: true
                });
                window.__lenis = lenis;

                if (hasGsap && hasST) {
                    lenis.on('scroll', window.ScrollTrigger.update);
                    window.gsap.ticker.add(function (time) { lenis.raf(time * 1000); });
                    window.gsap.ticker.lagSmoothing(0);
                } else {
                    function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
                    requestAnimationFrame(raf);
                }

                document.querySelectorAll('a[href^="#"]').forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        var id = link.getAttribute('href');
                        if (!id || id === '#') return;
                        var target = document.querySelector(id);
                        if (target) {
                            e.preventDefault();
                            lenis.scrollTo(target, { offset: -72 });
                        }
                    });
                });
            }

            // Animasi masuk ringan untuk halaman selain landing
            if (hasGsap && !document.body.dataset.customMotion) {
                var targets = document.querySelectorAll('main > div, main > section');
                if (targets.length) {
                    window.gsap.from(targets, {
                        y: 12,
                        opacity: 0,
                        duration: 0.42,
                        stagger: 0.05,
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