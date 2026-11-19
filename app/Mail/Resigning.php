<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Resigning extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $ticket;
    public $notifiable;
    public function __construct($ticket, $notifiable)
    {
        $this->ticket = $ticket;
        $this->notifiable = $notifiable;
    }

    /**
     * Get the message envelope.
     */
   public function build(){
    return $this->subject("Ticket Re-Assigned –" . $this->ticket->id)->markdown("emails.ticket-resign")->with([
        "ticket"=> $this->ticket,
        "notifiable"=> $this->notifiable,

    ]);
   }
}
