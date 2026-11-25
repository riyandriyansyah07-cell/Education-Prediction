<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Cegah Sebelum Terlambat') | Peringatan Dini Akademik</title>
    <meta name="description" content="Sistem peringatan dini akademik untuk mahasiswa FTII UHAMKA. Ketahui risiko nilaimu sebelum nilai akhir terbit.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
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
        /* === UNIFIED EDITORIAL ARCHITECTURAL SYSTEM (ANTI-SLOP · DESKTOP & MOBILE) === */
        :root {
            --bg: #f7f8fa;
            --surface: #ffffff;
            --sunken: #f1f5f9;
            --ink: #0f172a;
            --ink-2: #475569;
            --ink-3: #64748b;
            --line: #e2e8f0;
            --line-strong: #cbd5e1;
            --accent: #0284c7;
            --accent-ink: #ffffff;
            --accent-soft: #e0f2fe;
            --ok: #059669;
            --warn: #d97706;
            --bad: #dc2626;
            --shadow-card: 0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 12px -4px rgba(15, 23, 42, 0.04);
        }

        :root[data-theme='dark'] {
            --bg: #0a0d12;
            --surface: #111621;
            --sunken: #0d111a;
            --ink: #f1f5f9;
            --ink-2: #94a3b8;
            --ink-3: #64748b;
            --line: #1d2536;
            --line-strong: #2c374e;
            --accent: #38bdf8;
            --accent-ink: #090b10;
            --accent-soft: #102536;
            --ok: #34d399;
            --warn: #fbbf24;
            --bad: #f87171;
            --shadow-card: 0 1px 2px rgba(0, 0, 0, 0.28);
        }

        /* Matikan keyframe .rise bawaan app.css agar tidak bentrok dengan GSAP */
        .rise { animation: none !important; }

        html {
            scroll-behavior: smooth;
            -webkit-text-size-adjust: 100%;
        }

        body {
            background-color: var(--bg);
            color: var(--ink);
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* Tipografi & Ritme Editorial */
        main h1 {
            font-size: clamp(1.55rem, 2.6vw, 2.15rem) !important;
            font-weight: 650;
            line-height: 1.2;
            letter-spacing: -0.025em;
        }
        main h2 {
            font-weight: 600;
            letter-spacing: -0.015em;
            line-height: 1.3;
        }
        main h3 {
            font-weight: 600;
            letter-spacing: -0.01em;
        }
        .num, .mono {
            font-variant-numeric: tabular-nums;
        }

        /* Sidebar & Mobile Drawer */
        .sidebar {
            background: var(--bg) !important;
            border-right: 1px solid var(--line) !important;
            padding: 1.25rem 0.95rem !important;
        }
        .nav-link {
            display: flex !important;
            align-items: center;
            gap: 0.7rem;
            padding: 0.62rem 0.85rem !important;
            border-radius: 10px !important;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--ink-2);
            border-left: 2px solid transparent !important;
            transition: background-color 140ms ease, color 140ms ease, border-color 140ms ease !important;
        }
        .nav-link:hover {
            background: color-mix(in oklab, var(--ink) 5%, transparent) !important;
            color: var(--ink) !important;
        }
        .nav-link[aria-current='page'],
        .nav-link.is-active {
            background: var(--surface) !important;
            color: var(--ink) !important;
            border-left: 2px solid var(--accent) !important;
            box-shadow: var(--shadow-card);
        }

        /* Backdrop Mobile Sidebar */
        .app-nav-backdrop {
            position: fixed;
            inset: 0;
            z-index: 35;
            background: rgba(2, 6, 23, 0.56);
            backdrop-filter: blur(2px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 180ms ease;
        }
        .app-nav-backdrop.is-open {
            opacity: 1;
            pointer-events: auto;
        }
        @media (min-width: 1024px) {
            .app-nav-backdrop { display: none !important; }
        }

        /* Kartu & Permukaan Seragam */
        main .bg-surface {
            background: var(--surface) !important;
            border: 1px solid var(--line) !important;
            border-radius: 14px !important;
            box-shadow: var(--shadow-card);
            transition: border-color 150ms ease;
        }
        main .bg-surface:hover {
            border-color: var(--line-strong) !important;
        }

        /* Tombol Proporsional & Ramah Sentuh */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 40px;
            padding: 0.5rem 1rem;
            border-radius: 10px !important;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.2;
            cursor: pointer;
            transition: background-color 140ms ease, border-color 140ms ease, opacity 140ms ease, transform 100ms ease !important;
        }
        .btn:active { transform: translateY(0.5px); opacity: 0.9; }
        .btn-sm {
            min-height: 36px;
            padding: 0.42rem 0.82rem;
            font-size: 0.8125rem;
        }
        .btn-primary {
            background: var(--ink) !important;
            color: var(--bg) !important;
            border: 1px solid var(--ink) !important;
        }
        .btn-primary:hover { opacity: 0.9; }
        .btn-quiet {
            background: var(--surface) !important;
            border: 1px solid var(--line-strong) !important;
            color: var(--ink) !important;
        }
        .btn-quiet:hover {
            background: var(--sunken) !important;
            border-color: var(--ink-3) !important;
        }
        .btn-danger {
            background: color-mix(in srgb, var(--bad) 10%, var(--surface)) !important;
            border: 1px solid color-mix(in srgb, var(--bad) 35%, var(--line)) !important;
            color: var(--bad) !important;
        }
        .btn-danger:hover {
            background: color-mix(in srgb, var(--bad) 18%, var(--surface)) !important;
        }

        /* Formulir & Input Nyaman */
        .label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--ink-2);
            margin-bottom: 0.4rem;
        }
        .input {
            width: 100%;
            min-height: 42px;
            padding: 0.55rem 0.85rem;
            border-radius: 10px !important;
            background: var(--sunken) !important;
            border: 1px solid var(--line-strong) !important;
            color: var(--ink);
            font-size: 0.875rem;
            line-height: 1.45;
            transition: border-color 140ms ease, box-shadow 140ms ease;
        }
        textarea.input { min-height: 92px; padding-top: 0.65rem; padding-bottom: 0.65rem; }
        .input:focus {
            outline: none !important;
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 3px color-mix(in oklab, var(--accent) 18%, transparent) !important;
        }
        .field-error {
            margin-top: 0.35rem;
            font-size: 0.78rem;
            color: var(--bad);
        }

        /* Tabel Rapi, Lega & Aman di Layar HP */
        main .overflow-x-auto {
            -webkit-overflow-scrolling: touch;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: var(--surface);
            box-shadow: var(--shadow-card);
        }
        :is(.bg-surface, .adm-panel, .dpa-panel, .skpi-card) .overflow-x-auto {
            border-radius: 10px;
            box-shadow: none;
            background: transparent;
        }
        .tbl {
            width: 100%;
            border-collapse: collapse;
        }
        .tbl thead th {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--ink-3);
            padding: 0.85rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--line);
            background: color-mix(in srgb, var(--bg) 55%, var(--surface));
            white-space: nowrap;
        }
        .tbl tbody td {
            padding: 0.9rem 1rem;
            border-bottom: 1px solid var(--line);
            vertical-align: middle;
        }
        .tbl tbody tr:last-child td { border-bottom: none; }
        .tbl tbody tr:hover { background-color: color-mix(in oklab, var(--ink) 3%, transparent); }

        .risk-chip {
            border-radius: 999px !important;
            font-weight: 600 !important;
            letter-spacing: 0.01em;
        }

        /* Optimalisasi Khusus Layar HP (< 640px) */
        @media (max-width: 640px) {
            .input, select.input, textarea.input {
                font-size: 16px !important; /* Cegah auto-zoom iOS Safari */
                min-height: 44px;
            }
            .btn { min-height: 44px; }
            .btn-sm { min-height: 40px; }
            .tbl thead th { padding: 0.7rem 0.75rem; }
            .tbl tbody td { padding: 0.75rem 0.75rem; font-size: 0.84rem; }
        }
    </style>
</head>
<body class="min-h-dvh">
    @yield('body')

    {{-- GSAP + ScrollTrigger + Lenis (smoothWheel nonaktif agar scroll wheel/trackpad tetap native GPU 60fps) --}}
    <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.18/dist/lenis.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/gsap.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/ScrollTrigger.min.js" defer></script>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduceMotion) return;

            var hasGsap = typeof window.gsap !== 'undefined';
            var hasST = typeof window.ScrollTrigger !== 'undefined';
            var isLanding = Boolean(document.body.dataset.customMotion);

            if (hasGsap && hasST) {
                window.gsap.registerPlugin(window.ScrollTrigger);
                window.ScrollTrigger.config({ ignoreMobileResize: true });
            }

            if (isLanding && typeof window.Lenis !== 'undefined') {
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

            if (hasGsap && !isLanding) {
                var main = document.querySelector('main');
                if (main) {
                    window.gsap.from(main, {
                        opacity: 0,
                        duration: 0.2,
                        ease: 'power1.out',
                        clearProps: 'opacity'
                    });
                }
            }
        });
    </script>
</body>
</html>