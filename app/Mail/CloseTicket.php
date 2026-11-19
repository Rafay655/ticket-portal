<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CloseTicket extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $ticket;
    public $notifiable;
    public $comment;
    public function __construct($ticket, $notifiable,$comment)
    {
        //
        $this->ticket = $ticket;
        $this->notifiable = $notifiable;
        $this->comment = $comment;
    }

    /**
     * Get the message envelope.
     */
    public function build()
    {
        return $this->subject('Ticket Closed – ' . $this->ticket->id)
        ->markdown("emails.ticket-close")
        ->with([
            'ticket' => $this->ticket,
            'notifiable' => $this->notifiable,
            'comment' => $this->comment,

        ]);
    }
}
