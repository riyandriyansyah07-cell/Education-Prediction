<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:24px;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1c2530;">
<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px;">
    <p style="margin:0 0 4px;font-size:13px;color:#5b6773;">{{ config('app.name') }} — FTII UHAMKA</p>

    @if ($ticket->status === 'disetujui')
        <h1 style="margin:0 0 16px;font-size:20px;color:#146c43;">Pengajuan E-Ticket Disetujui</h1>
        <p style="margin:0 0 12px;line-height:1.6;">
            Halo <strong>{{ $ticket->name }}</strong>, pengajuan tiket <strong>{{ $ticket->ticket_code }}</strong> ({{ $ticket->typeLabel() }}) telah disetujui oleh Admin Sekretariat FTII.
        </p>
        <div style="margin:16px 0;padding:16px;background:#f4f6f8;border-radius:8px;line-height:1.7;">
            <div><strong>NIM / ID Pengguna:</strong> {{ $ticket->username }}</div>
            @if ($ticket->program_studi)
                <div><strong>Program Studi:</strong> {{ $ticket->program_studi }}</div>
            @endif
        </div>
        @if ($ticket->admin_note)
            <p style="margin:0 0 16px;line-height:1.6;"><strong>Catatan Sekretariat:</strong> {{ $ticket->admin_note }}</p>
        @endif
        @if ($setPasswordUrl)
            <p style="margin:0 0 16px;line-height:1.6;">
                Demi keamanan, kata sandi tidak dikirim melalui email. Klik tombol di bawah untuk mengatur kata sandi pribadimu (tautan berlaku sekali pakai selama {{ $expiresHours }} jam):
            </p>
            <p style="margin:20px 0 12px;">
                <a href="{{ $setPasswordUrl }}" style="display:inline-block;padding:10px 18px;background:#0f766e;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;">Atur Kata Sandi Sekarang</a>
            </p>
            <p style="margin:0;font-size:12px;color:#5b6773;word-break:break-all;">
                Jika tombol tidak berfungsi, salin tautan berikut ke peramban: {{ $setPasswordUrl }}
            </p>
        @else
            <p style="margin:20px 0 0;">
                <a href="{{ $loginUrl }}" style="display:inline-block;padding:10px 18px;background:#0f766e;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;">Masuk ke Aplikasi</a>
            </p>
        @endif
    @else
        <h1 style="margin:0 0 16px;font-size:20px;color:#b42318;">Pengajuan E-Ticket Ditolak</h1>
        <p style="margin:0 0 12px;line-height:1.6;">
            Halo <strong>{{ $ticket->name }}</strong>, pengajuan tiket <strong>{{ $ticket->ticket_code }}</strong> ({{ $ticket->typeLabel() }}) belum dapat disetujui oleh Admin Sekretariat FTII.
        </p>
        @if ($ticket->admin_note)
            <div style="margin:16px 0;padding:16px;background:#fef2f2;border-radius:8px;line-height:1.6;">
                <strong>Alasan Penolakan:</strong><br>{{ $ticket->admin_note }}
            </div>
        @endif
        <p style="margin:16px 0 0;line-height:1.6;">
            Kamu dapat memeriksa kembali data dan mengajukan ulang melalui <a href="{{ $ticketUrl }}" style="color:#0f766e;">halaman E-Ticket</a>.
        </p>
    @endif
</div>
</body>
</html>