<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Escalation extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $notifiable;
   
    /**
     * Create a new message instance.
     */
    public function __construct($ticket,$notifiable, )
    {
        //
        $this->ticket = $ticket;
        $this->notifiable = $notifiable;
    }

    /**
     * Get the message envelope.
     */
    public function build()
    {
        return  $this->subject('Ticket Escalated – ' . $this->ticket->id)
                    ->markdown('emails.ticket-escalated')
                    ->with([
                        'ticket' => $this->ticket,
                        'notifiable' => $this->notifiable,
                        
                    ]);
    }
}
