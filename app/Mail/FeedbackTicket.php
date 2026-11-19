<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackTicket extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $notifiable;
    public $comment;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($ticket, $notifiable, $comment)
    {
        $this->ticket = $ticket;
        $this->notifiable = $notifiable;
        $this->comment = $comment;
       
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return  $this->subject('Ticket Reviewed – ' . $this->ticket->id)
                    ->markdown('emails.ticket-feedback')
                    ->with([
                        'ticket' => $this->ticket,
                        'notifiable' => $this->notifiable,
                        'comment' => $this->comment,
                        
                    ]);
    }
}
