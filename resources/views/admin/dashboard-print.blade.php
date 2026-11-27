<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Analisis Risiko Akademik — FTII UHAMKA</title>
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
            margin: 0;
            padding: 24px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--ink);
            background: #ffffff;
            font-size: 12px;
            line-height: 1.45;
        }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--bg);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 7px;
            border: 1px solid var(--line);
            background: #ffffff;
            color: var(--ink);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary { background: var(--accent); border-color: var(--accent); color: #ffffff; }
        .header {
            border-bottom: 2px solid var(--accent);
            padding-bottom: 12px;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
        }
        .header h1 { margin: 0; font-size: 19px; color: var(--ink); }
        .header p { margin: 4px 0 0; color: var(--ink-2); font-size: 11.5px; }
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }
        .kpi {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 10px 12px;
            background: var(--bg);
        }
        .kpi-label { font-size: 10.5px; color: var(--ink-2); text-transform: uppercase; letter-spacing: 0.03em; }
        .kpi-val { font-size: 18px; font-weight: 700; margin-top: 3px; font-variant-numeric: tabular-nums; }
        h2 { font-size: 13.5px; margin: 18px 0 8px; color: var(--ink); }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        th, td {
            border: 1px solid var(--line);
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: var(--accent);
            color: #ffffff;
            font-weight: 600;
            white-space: nowrap;
        }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-tinggi { background: #fee2e2; color: var(--bad); }
        .badge-sedang { background: #fef3c7; color: var(--warn); }
        .badge-rendah { background: #dcfce7; color: var(--ok); }
        .badge-belum { background: #e2e8f0; color: var(--ink-2); }
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
            <strong>Pratinjau Laporan Cetak</strong> &middot;
            Menampilkan <strong>{{ number_format($filteredStudents->count()) }}</strong> dari {{ number_format($totals['students']) }} mahasiswa
        </div>
        <div style="display:flex; gap:8px;">
            <button type="button" class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
            <button type="button" class="btn" onclick="window.close()">Tutup</button>
        </div>
    </div>

    <header class="header">
        <div>
            <h1>Laporan Ringkasan &amp; Analisis Risiko Akademik</h1>
            <p>Sistem Peringatan Dini Akademik &ldquo;Cegah Sebelum Terlambat&rdquo; &middot; FTII UHAMKA</p>
        </div>
        <div style="text-align:right; font-size:11px; color:var(--ink-2);">
            <div>Dicetak: <strong>{{ $generatedAt->format('d/m/Y H:i') }} WIB</strong></div>
            <div>Model: {{ $activeModel ? $activeModel->version : 'Aturan bawaan' }}</div>
        </div>
    </header>

    <section class="kpi-grid">
        <div class="kpi">
            <div class="kpi-label">Total Mahasiswa</div>
            <div class="kpi-val">{{ number_format($totals['students']) }}</div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Risiko Tinggi</div>
            <div class="kpi-val" style="color:var(--bad);">{{ number_format($riskCounts['tinggi']) }} ({{ $riskPcts['tinggi'] }}%)</div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Risiko Sedang</div>
            <div class="kpi-val" style="color:var(--warn);">{{ number_format($riskCounts['sedang']) }} ({{ $riskPcts['sedang'] }}%)</div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Risiko Rendah</div>
            <div class="kpi-val" style="color:var(--ok);">{{ number_format($riskCounts['rendah']) }} ({{ $riskPcts['rendah'] }}%)</div>
        </div>
    </section>

    <h2>Rekapitulasi Data Mahasiswa Terfilter ({{ number_format($filteredStudents->count()) }} Baris)</h2>
    <table>
        <thead>
            <tr>
                <th class="num">#</th>
                @foreach ($selectedCols as $colKey)
                    <th>{{ $exportColumns[$colKey] ?? $colKey }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($filteredStudents as $i => $row)
                @php
                    $stu = $row['student'];
                    $res = $row['result'];
                    $features = is_array($res?->features) ? $res->features : [];
                    $cells = [
                        'nim' => $stu->username,
                        'nama' => $stu->name,
                        'prodi' => $row['program_studi'],
                        'angkatan' => $row['angkatan'],
                        'status_akun' => $stu->is_active ? 'Aktif' : 'Nonaktif',
                        'dpa' => $stu->dpa?->name ?? 'Belum ditetapkan',
                        'izin_dpa' => $row['consent_granted'] ? 'Diizinkan' : 'Belum',
                        'tingkat' => $row['level'],
                        'skor' => $res ? number_format((float) $res->score, 2) : '-',
                        'semester' => $res?->semester ?? '-',
                        'ips' => isset($features['ips']) ? number_format((float) $features['ips'], 2) : '-',
                        'kehadiran' => isset($features['attendance']) ? number_format((float) $features['attendance'], 1).'%' : '-',
                        'pengayaan' => $row['enrichment_converted_sks'].'/'.\App\Support\EnrichmentCatalog::TARGET_SKS,
                        'tindak_lanjut' => \App\Models\StudentSetting::followUpLabelFor($row['follow_up_status']),
                        'faktor' => $row['top_factor'] ?? '-',
                    ];
                @endphp
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    @foreach ($selectedCols as $colKey)
                        @if ($colKey === 'tingkat')
                            <td><span class="badge badge-{{ $row['level'] }}">{{ strtoupper($row['level']) }}</span></td>
                        @elseif (in_array($colKey, ['skor', 'semester', 'ips', 'kehadiran', 'pengayaan'], true))
                            <td class="num">{{ $cells[$colKey] }}</td>
                        @else
                            <td>{{ $cells[$colKey] }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($selectedCols) + 1 }}" style="text-align:center; color:var(--ink-2); padding:16px;">
                        Tidak ada data mahasiswa sesuai filter aktif.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
