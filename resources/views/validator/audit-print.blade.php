<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Audit &amp; Validasi AI — FTII UHAMKA</title>
    <style>
        :root {
            --ink: #0f172a;
            --ink-2: #475569;
            --line: #cbd5e1;
            --bg: #f8fafc;
            --accent: #0f766e;
            --bad: #b91c1c;
            --warn: #b45309;
            --ok: #15803d;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 24px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--ink); background: #ffffff; font-size: 11.5px; line-height: 1.45;
        }
        .toolbar {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: 10px; padding: 12px 16px; margin-bottom: 18px;
            border: 1px solid var(--line); border-radius: 10px; background: var(--bg);
        }
        .btn {
            display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px;
            border-radius: 7px; border: 1px solid var(--line); background: #ffffff;
            color: var(--ink); font-size: 12px; font-weight: 600; cursor: pointer;
        }
        .btn-primary { background: var(--accent); border-color: var(--accent); color: #ffffff; }
        .header {
            border-bottom: 2px solid var(--accent); padding-bottom: 12px; margin-bottom: 16px;
            display: flex; justify-content: space-between; align-items: flex-end; gap: 16px;
        }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 4px 0 0; color: var(--ink-2); font-size: 11px; }
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 16px; }
        .kpi { border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; background: var(--bg); }
        .kpi-label { font-size: 10px; color: var(--ink-2); text-transform: uppercase; letter-spacing: 0.03em; }
        .kpi-val { font-size: 17px; font-weight: 700; margin-top: 3px; font-variant-numeric: tabular-nums; }
        table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
        th, td { border: 1px solid var(--line); padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: var(--accent); color: #ffffff; font-weight: 600; white-space: nowrap; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 9999px; font-size: 9.5px; font-weight: 700; text-transform: uppercase; }
        .b-bad { background: #fee2e2; color: var(--bad); }
        .b-warn { background: #fef3c7; color: var(--warn); }
        .b-ok { background: #dcfce7; color: var(--ok); }
        .b-muted { background: #e2e8f0; color: var(--ink-2); }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <div>
            <strong>Pratinjau Laporan Audit AI</strong> &middot;
            Menampilkan <strong>{{ number_format($allFilteredItems->count()) }}</strong> dari {{ number_format($stats['total']) }} hasil
        </div>
        <div style="display:flex; gap:8px;">
            <button type="button" class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
            <button type="button" class="btn" onclick="window.close()">Tutup</button>
        </div>
    </div>

    <header class="header">
        <div>
            <h1>Laporan Audit &amp; Validasi Hasil AI Gemini</h1>
            <p>Human-in-the-Loop Evaluation &middot; Sistem Peringatan Dini Akademik FTII UHAMKA</p>
        </div>
        <div style="text-align:right; font-size:11px; color:var(--ink-2);">
            <div>Dicetak: <strong>{{ $generatedAt->format('d/m/Y H:i') }} WIB</strong></div>
            <div>Cakupan: {{ $filters['cakupan'] === 'semua' ? 'Semua semester' : 'Semester terbaru' }}</div>
        </div>
    </header>

    <section class="kpi-grid">
        <div class="kpi">
            <div class="kpi-label">Total Hasil</div>
            <div class="kpi-val">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Akurasi AI (Sesuai)</div>
            <div class="kpi-val" style="color:var(--ok);">{{ $stats['accuracy_pct'] !== null ? $stats['accuracy_pct'].'%' : '—' }}</div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Prioritas Selisih</div>
            <div class="kpi-val" style="color:var(--bad);">{{ number_format($stats['disagreement']) }}</div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Tervalidasi</div>
            <div class="kpi-val">{{ number_format($stats['validated']) }} / {{ number_format($stats['total']) }}</div>
        </div>
    </section>

    <table>
        <thead>
            <tr>
                <th class="num">#</th>
                <th>NIM</th>
                <th>Mahasiswa</th>
                <th>Prodi</th>
                <th class="num">Sem</th>
                <th>Sumber</th>
                <th>AI / Sistem</th>
                <th>Rumus</th>
                <th class="num">&Delta; Skor</th>
                <th class="num">IPS</th>
                <th class="num">Hadir</th>
                <th>Status Validasi</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($allFilteredItems as $i => $item)
                @php
                    $res = $item['result'];
                    $stu = $item['student'];
                    $val = $item['validation'];
                @endphp
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>{{ $stu?->username ?? substr((string) $res->student_ref, 0, 10) }}</td>
                    <td>{{ $stu?->name ?? 'Mahasiswa' }}</td>
                    <td>{{ $stu?->program_studi ?? '-' }}</td>
                    <td class="num">{{ $res->semester }}</td>
                    <td>{{ $item['source_label'] }}</td>
                    <td>
                        <span class="badge {{ $item['ai_level'] === 'tinggi' ? 'b-bad' : ($item['ai_level'] === 'sedang' ? 'b-warn' : 'b-ok') }}">{{ strtoupper($item['ai_level']) }}</span>
                        ({{ number_format($item['ai_score'], 2) }})
                    </td>
                    <td>
                        <span class="badge {{ $item['rule_level'] === 'tinggi' ? 'b-bad' : ($item['rule_level'] === 'sedang' ? 'b-warn' : 'b-ok') }}">{{ strtoupper($item['rule_level']) }}</span>
                        ({{ number_format($item['rule_score'], 2) }})
                    </td>
                    <td class="num" style="{{ $item['is_disagreement'] ? 'color:var(--bad);font-weight:700;' : '' }}">{{ number_format($item['score_delta'], 2) }}</td>
                    <td class="num">{{ $item['ips'] !== null ? number_format($item['ips'], 2) : '-' }}</td>
                    <td class="num">{{ $item['attendance'] !== null ? number_format($item['attendance'], 1).'%' : '-' }}</td>
                    <td>
                        @if ($val)
                            <span class="badge {{ $val->verdict === 'sesuai' ? 'b-ok' : 'b-warn' }}">{{ $val->verdictLabel() }}</span>
                        @else
                            <span class="badge b-muted">Belum</span>
                        @endif
                    </td>
                    <td>{{ $val?->notes ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="13" style="text-align:center; padding:16px; color:var(--ink-2);">Tidak ada data sesuai filter aktif.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
