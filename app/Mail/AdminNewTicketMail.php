<?php

namespace App\Mail;

use App\Models\AccountTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNewTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly AccountTicket $ticket,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.config('app.name')."] E-Ticket Baru: {$this->ticket->name} ({$this->ticket->username})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-new-ticket',
            with: [
                'ticket' => $this->ticket,
                'adminTicketsUrl' => route('admin.tiket'),
            ],
        );
    }
}
