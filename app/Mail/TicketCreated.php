<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $notifiable;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($ticket, $notifiable)
    {
        $this->ticket = $ticket;
        $this->notifiable = $notifiable;
       
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return  $this->subject('New Ticket Created - Ticket ' . $this->ticket->id)
                    ->markdown('emails.ticket-created')
                    ->with([
                        'ticket' => $this->ticket,
                        'notifiable' => $this->notifiable,
                        
                    ]);
    }
}
