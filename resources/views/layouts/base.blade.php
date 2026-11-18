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

        @keyframes proFadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        main > div, main > section {
            animation: proFadeUp 300ms cubic-bezier(0.16, 1, 0.3, 1) both;
        }
    </style>
</head>
<body class="min-h-dvh">
    @yield('body')
</body>
</html>