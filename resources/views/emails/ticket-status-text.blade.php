{{ config('app.name') }} — FTII UHAMKA

Halo {{ $ticket->name }},

@if ($ticket->status === 'disetujui')
Pengajuan E-Ticket {{ $ticket->ticket_code }} ({{ $ticket->typeLabel() }}) telah DISETUJUI.

NIM / ID Pengguna : {{ $ticket->username }}
@if ($ticket->program_studi)
Program Studi     : {{ $ticket->program_studi }}
@endif
@if ($plainPassword)
Kata Sandi Awal   : {{ $plainPassword }}
@endif
@if ($ticket->admin_note)
Catatan           : {{ $ticket->admin_note }}
@endif

Silakan masuk di: {{ $loginUrl }}
@else
Pengajuan E-Ticket {{ $ticket->ticket_code }} ({{ $ticket->typeLabel() }}) DITOLAK.

@if ($ticket->admin_note)
Alasan Penolakan: {{ $ticket->admin_note }}
@endif

Cek atau ajukan ulang di: {{ $ticketUrl }}
@endif