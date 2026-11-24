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
            background-image: radial-gradient(color-mix(in oklab, var(--ink) 7%, transparent) 1px, transparent 1px);
            background-size: 24px 24px;
            color: var(--ink);
        }

        .sidebar {
            background: color-mix(in oklab, var(--bg) 90%, var(--surface)) !important;
            backdrop-filter: blur(16px);
            border-right: 1px solid var(--line) !important;
        }

        main .bg-surface {
            background: var(--surface) !important;
            border: 1px solid var(--line) !important;
            border-radius: 12px !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04), 0 4px 20px -6px rgba(0, 0, 0, 0.25);
            transition: border-color 180ms ease, transform 180ms cubic-bezier(0.16, 1, 0.3, 1), box-shadow 180ms ease;
        }

        main .bg-surface:hover {
            border-color: var(--line-strong) !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06), 0 10px 28px -8px rgba(0, 0, 0, 0.35);
        }

        .btn {
            border-radius: 8px !important;
            font-weight: 500;
            transition: transform 130ms ease, background-color 150ms ease, border-color 150ms ease, box-shadow 150ms ease !important;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn:active { transform: translateY(0) scale(0.985); }

        .btn-primary {
            background: var(--ink) !important;
            color: var(--bg) !important;
            border-color: var(--ink) !important;
            box-shadow: 0 2px 10px -2px rgba(0, 0, 0, 0.25);
        }
        .btn-primary:hover {
            opacity: 0.92;
            box-shadow: 0 6px 16px -4px rgba(0, 0, 0, 0.35);
        }

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
            transition: background-color 140ms ease, color 140ms ease, transform 140ms ease !important;
        }
        .nav-link:hover {
            background: color-mix(in oklab, var(--ink) 5%, transparent) !important;
            transform: translateX(2px);
        }
        .nav-link[aria-current='page'],
        .nav-link.is-active {
            background: var(--surface) !important;
            color: var(--ink) !important;
            border-left: 2px solid var(--accent) !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
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

    {{-- GSAP + ScrollTrigger + Lenis Smooth Scroll --}}
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

            // Lenis smooth scrolling tersinkron dengan GSAP ticker & ScrollTrigger
            if (typeof window.Lenis !== 'undefined') {
                var lenis = new window.Lenis({
                    duration: 1.05,
                    easing: function (t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)); },
                    smoothWheel: true,
                    touchMultiplier: 1.5
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

                // Smooth scroll untuk tautan anchor (#bento, #peran, dll.)
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

            // Animasi masuk halus global untuk halaman selain landing (landing punya koreografi khusus)
            if (hasGsap && !document.body.dataset.customMotion) {
                var rises = document.querySelectorAll('main .rise, main > div > h1, main > h1');
                if (rises.length) {
                    window.gsap.from(rises, {
                        y: 16,
                        opacity: 0,
                        duration: 0.55,
                        stagger: 0.06,
                        ease: 'power3.out',
                        clearProps: 'transform,opacity'
                    });
                }

                var cards = document.querySelectorAll('main section, main .bg-surface, main .mt-card');
                if (cards.length && hasST) {
                    cards.forEach(function (el) {
                        if (el.closest('.lg-preview')) return;
                        window.gsap.from(el, {
                            y: 20,
                            opacity: 0,
                            duration: 0.6,
                            ease: 'power3.out',
                            clearProps: 'transform,opacity',
                            scrollTrigger: {
                                trigger: el,
                                start: 'top 90%',
                                once: true
                            }
                        });
                    });
                }
            }
        });
    </script>
</body>
</html>