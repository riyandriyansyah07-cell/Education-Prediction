<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>E-Ticket Baru Masuk</title>
</head>
<body style="margin:0;padding:24px;background:#f7f9fc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#0b1220;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e2eaf5;border-radius:16px;padding:24px;">
        <p style="margin:0 0 6px;font-size:12px;font-weight:700;color:#1d4e89;text-transform:uppercase;letter-spacing:0.04em;">
            Notifikasi Admin Sekretariat &middot; FTII UHAMKA
        </p>
        <h1 style="margin:0 0 12px;font-size:20px;line-height:1.3;color:#0b1220;">
            Ada E-Ticket Pengajuan Akun Baru
        </h1>
        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
            Pengajuan baru masuk ke antrean verifikasi dan menunggu persetujuan Admin Sekretariat:
        </p>

        <div style="background:#f7f9fc;border:1px solid #e2eaf5;border-radius:12px;padding:14px 16px;margin-bottom:20px;font-size:13.5px;line-height:1.65;">
            <div><strong>Kode Tiket:</strong> {{ $ticket->ticket_code }}</div>
            <div><strong>Jenis Pengajuan:</strong> {{ $ticket->typeLabel() }}</div>
            <div><strong>Pemohon:</strong> {{ $ticket->name }} ({{ $ticket->username }})</div>
            @if ($ticket->program_studi)
                <div><strong>Program Studi:</strong> {{ $ticket->program_studi }}</div>
            @endif
            <div><strong>Email Pemohon:</strong> {{ $ticket->email }}</div>
            @if ($ticket->notes)
                <div><strong>Catatan:</strong> {{ $ticket->notes }}</div>
            @endif
        </div>

        <a href="{{ $adminTicketsUrl }}" style="display:inline-block;padding:11px 22px;border-radius:9999px;background:#0b1220;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;">
            Buka Panel Tiket Pengajuan &rarr;
        </a>

        <p style="margin:18px 0 0;font-size:12px;color:#64748b;line-height:1.5;">
            Email ini dikirim otomatis oleh sistem <strong>{{ config('app.name') }}</strong> agar Admin tetap menerima pemberitahuan meskipun sedang berada di luar website.
        </p>
    </div>
</body>
</html>
