<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Cegah Sebelum Terlambat') | Peringatan Dini Akademik</title>
    <meta name="description" content="Sistem peringatan dini akademik untuk mahasiswa FTII UHAMKA. Ketahui risiko nilaimu sebelum nilai akhir terbit.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (!t) t = 'light';
                document.documentElement.dataset.theme = t;
            } catch (e) { document.documentElement.dataset.theme = 'light'; }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* === UNIFIED EDITORIAL ARCHITECTURAL SYSTEM (ANTI-SLOP · DESKTOP & MOBILE) === */
        :root {
            --bg: #f7f9fc;
            --surface: #ffffff;
            --sunken: #f3f7fc;
            --ink: #0b1220;
            --ink-2: #475569;
            --ink-3: #7c8aa0;
            --line: #e2eaf5;
            --line-strong: #cdd9ea;
            --accent: #1d4e89;
            --accent-ink: #ffffff;
            --accent-soft: #eef4fb;
            --ok: #0f766e;
            --warn: #b45309;
            --bad: #dc2626;
            --shadow-card: 0 1px 2px rgba(11, 18, 32, 0.03);
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
            border-radius: 9999px !important;
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
            background: var(--accent-soft) !important;
            color: var(--accent) !important;
            font-weight: 600;
            border-left: 2px solid transparent !important;
            box-shadow: none;
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
            border-radius: 20px !important;
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
            border-radius: 12px !important;
            background: var(--sunken) !important;
            border: 1px solid var(--line) !important;
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

        /* Global Custom Confirm Modal (Menggantikan popup confirm() bawaan browser) */
        .app-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(2, 6, 23, 0.65);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }
        .app-modal-overlay.is-open { display: flex; }
        .app-modal-card {
            width: 100%;
            max-width: 420px;
            border-radius: 16px;
            border: 1px solid var(--line-strong);
            background: var(--surface);
            padding: 1.4rem 1.5rem;
            box-shadow: 0 24px 60px -16px rgba(0, 0, 0, 0.45);
            animation: appModalPop 160ms cubic-bezier(0.22, 1, 0.36, 1);
        }
        @keyframes appModalPop {
            from { opacity: 0; transform: scale(0.96) translateY(6px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Custom Inline Form Validation (Menggantikan tooltip "Please fill out this field." bawaan browser) */
        .input.is-invalid {
            border-color: var(--bad) !important;
            box-shadow: 0 0 0 3px color-mix(in oklab, var(--bad) 16%, transparent) !important;
        }
        .app-field-msg {
            margin-top: 0.35rem;
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--bad);
            display: flex;
            align-items: center;
            gap: 0.38rem;
            line-height: 1.4;
            animation: appModalPop 140ms ease;
        }
        .app-field-msg::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 9999px;
            background: var(--bad);
            flex-shrink: 0;
        }

        /* Custom Slim Scrollbar Global (Clean, Simpel, Tanpa Panah Bawaan Windows) */
        * {
            scrollbar-width: thin;
            scrollbar-color: var(--line-strong) transparent;
        }
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: color-mix(in srgb, var(--ink-3) 38%, transparent);
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: color-mix(in srgb, var(--ink-3) 65%, transparent);
        }
        ::-webkit-scrollbar-button {
            display: none;
            width: 0;
            height: 0;
        }
        ::-webkit-scrollbar-corner {
            background: transparent;
        }

        /* Clean Accordion & Action-Add Trigger (tanpa panah segitiga bawaan browser) */
        details.ui-acc {
            border-radius: 12px;
            border: 1px solid var(--line);
            background: var(--surface);
            padding: 0.9rem 1.15rem;
            transition: border-color 160ms ease, background-color 160ms ease;
        }
        details.ui-acc.is-add {
            border: 1.5px dashed color-mix(in srgb, var(--accent) 42%, var(--line));
            background: color-mix(in srgb, var(--accent) 4%, var(--bg));
        }
        details.ui-acc.is-add:hover,
        details.ui-acc.is-add[open] {
            border-style: solid;
            border-color: color-mix(in srgb, var(--accent) 55%, var(--line));
            background: var(--bg);
        }
        details.ui-acc > summary.ui-acc-head {
            list-style: none;
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }
        details.ui-acc > summary.ui-acc-head::-webkit-details-marker {
            display: none;
        }
        .ui-acc-left {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            min-width: 0;
        }
        .ui-acc-icon {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: color-mix(in srgb, var(--accent) 13%, var(--surface));
            color: var(--accent);
            border: 1px solid color-mix(in srgb, var(--accent) 28%, var(--line));
        }
        .ui-acc-title {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--ink);
            line-height: 1.3;
        }
        .ui-acc-sub {
            display: block;
            font-size: 0.75rem;
            color: var(--ink-3);
            margin-top: 2px;
            line-height: 1.35;
        }
        .ui-acc-chev {
            width: 18px;
            height: 18px;
            color: var(--ink-3);
            flex-shrink: 0;
            transition: transform 180ms ease, color 180ms ease;
        }
        details.ui-acc[open] > summary.ui-acc-head .ui-acc-chev {
            transform: rotate(180deg);
            color: var(--accent);
        }
        .btn-add-soft {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.85rem;
            border-radius: 9px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--accent);
            background: color-mix(in srgb, var(--accent) 9%, var(--surface));
            border: 1px solid color-mix(in srgb, var(--accent) 32%, var(--line));
            cursor: pointer;
            transition: background-color 140ms ease, border-color 140ms ease;
        }
        .btn-add-soft:hover {
            background: color-mix(in srgb, var(--accent) 16%, var(--surface));
            border-color: color-mix(in srgb, var(--accent) 55%, var(--line));
        }
    </style>
</head>
<body class="min-h-dvh">
    @yield('body')


    <script>
        (function () {
            function getIndonesianMsg(el) {
                var v = el.validity;
                if (!v) return 'Periksa kembali isian ini.';
                if (v.valueMissing) {
                    if (el.tagName === 'SELECT') return 'Pilih salah satu opsi terlebih dahulu.';
                    if (el.type === 'file') return 'Pilih berkas yang akan diunggah.';
                    if (el.type === 'checkbox') return 'Centang kotak ini untuk melanjutkan.';
                    return 'Kolom ini wajib diisi.';
                }
                if (v.typeMismatch && el.type === 'email') return 'Masukkan alamat email yang valid (contoh: nama@uhamka.ac.id).';
                if (v.tooShort) return 'Minimal ' + el.getAttribute('minlength') + ' karakter.';
                if (v.tooLong) return 'Maksimal ' + el.getAttribute('maxlength') + ' karakter.';
                if (v.rangeUnderflow) return 'Nilai minimal ' + el.getAttribute('min') + '.';
                if (v.rangeOverflow) return 'Nilai maksimal ' + el.getAttribute('max') + '.';
                if (v.patternMismatch) return 'Format isian belum sesuai.';
                return 'Periksa kembali isian ini.';
            }

            function clearFieldError(el) {
                el.classList.remove('is-invalid');
                el.removeAttribute('aria-invalid');
                var next = el.nextElementSibling;
                if (next && next.classList.contains('app-field-msg')) {
                    next.remove();
                }
            }

            function showFieldError(el) {
                clearFieldError(el);
                el.classList.add('is-invalid');
                el.setAttribute('aria-invalid', 'true');
                var p = document.createElement('p');
                p.className = 'app-field-msg';
                p.setAttribute('role', 'alert');
                p.textContent = getIndonesianMsg(el);
                el.insertAdjacentElement('afterend', p);
            }

            function disableNativeBubbles() {
                document.querySelectorAll('form').forEach(function (f) {
                    if (!f.hasAttribute('novalidate')) f.setAttribute('novalidate', 'novalidate');
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', disableNativeBubbles);
            } else {
                disableNativeBubbles();
            }

            document.addEventListener('submit', function (e) {
                var form = e.target;
                if (!form || form.tagName !== 'FORM') return;
                var fields = Array.from(form.querySelectorAll('input, select, textarea')).filter(function (el) {
                    return !el.disabled && el.type !== 'hidden' && el.offsetParent !== null;
                });
                var firstInvalid = null;
                fields.forEach(function (el) {
                    if (!el.checkValidity()) {
                        showFieldError(el);
                        if (!firstInvalid) firstInvalid = el;
                    } else {
                        clearFieldError(el);
                    }
                });
                if (firstInvalid) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, true);

            ['input', 'change'].forEach(function (evt) {
                document.addEventListener(evt, function (e) {
                    var el = e.target;
                    if (el && el.classList && el.classList.contains('is-invalid') && el.checkValidity()) {
                        clearFieldError(el);
                    }
                }, true);
            });
        })();
    </script>

    {{-- Custom Confirm Modal Global (Pengganti dialog confirm() bawaan browser) --}}
    <div id="app-confirm-modal" class="app-modal-overlay no-print" role="dialog" aria-modal="true" aria-labelledby="app-confirm-title">
        <div class="app-modal-card">
            <div style="display:flex;align-items:flex-start;gap:0.9rem;">
                <span style="width:38px;height:38px;border-radius:10px;background:color-mix(in srgb, var(--bad) 14%, transparent);color:var(--bad);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </span>
                <div style="min-width:0;flex:1;">
                    <h3 id="app-confirm-title" class="text-base font-semibold text-ink" style="margin:0;">Konfirmasi Tindakan</h3>
                    <p id="app-confirm-msg" class="text-sm text-ink-2" style="margin:0.4rem 0 0;line-height:1.55;"></p>
                </div>
            </div>
            <div style="margin-top:1.35rem;display:flex;justify-content:flex-end;gap:0.6rem;">
                <button type="button" id="app-confirm-cancel" class="btn btn-quiet btn-sm">Batal</button>
                <button type="button" id="app-confirm-ok" class="btn btn-danger btn-sm" style="background:var(--bad) !important;color:#ffffff !important;border-color:var(--bad) !important;">Ya, Hapus</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var modal = document.getElementById('app-confirm-modal');
            var msgEl = document.getElementById('app-confirm-msg');
            var btnCancel = document.getElementById('app-confirm-cancel');
            var btnOk = document.getElementById('app-confirm-ok');
            if (!modal || !msgEl || !btnCancel || !btnOk) return;

            var pendingForm = null;

            function closeModal() {
                modal.classList.remove('is-open');
                pendingForm = null;
            }

            document.addEventListener('submit', function (e) {
                var form = e.target;
                if (!form || form.tagName !== 'FORM') return;
                var msg = form.getAttribute('data-confirm');
                if (!msg || form.dataset.confirmed === '1') return;

                e.preventDefault();
                e.stopImmediatePropagation();
                pendingForm = form;
                msgEl.textContent = msg;
                modal.classList.add('is-open');
                btnCancel.focus();
            }, true);

            btnCancel.addEventListener('click', closeModal);
            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeModal();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
            });
            btnOk.addEventListener('click', function () {
                if (!pendingForm) return;
                var f = pendingForm;
                closeModal();
                f.dataset.confirmed = '1';
                f.submit();
            });
        })();
    </script>


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