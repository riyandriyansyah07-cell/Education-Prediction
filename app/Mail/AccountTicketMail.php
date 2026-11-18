<?php

namespace App\Mail;

use App\Models\AccountTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly AccountTicket $ticket,
        public readonly ?string $plainPassword = null,
    ) {}

    public function envelope(): Envelope
    {
        $statusText = $this->ticket->status === AccountTicket::STATUS_APPROVED
            ? 'Disetujui - Akun Aktif'
            : 'Ditolak';

        return new Envelope(
            subject: '['.config('app.name')."] Tiket {$this->ticket->ticket_code} {$statusText}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-status',
            text: 'emails.ticket-status-text',
            with: [
                'ticket' => $this->ticket,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => route('login'),
                'ticketUrl' => route('tiket.form', ['kode' => $this->ticket->ticket_code]),
            ],
        );
    }
}