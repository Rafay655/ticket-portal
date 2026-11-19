<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommentAdded extends Mailable
{
    use Queueable, SerializesModels;
    public $ticket;
    public $notifiable;
    public $comment;
    
    public function __construct($ticket,$notifiable, $comment)
    {
        //
        $this->ticket = $ticket;
        $this->notifiable = $notifiable;
        $this->comment = $comment;  
    }

    
    public function build()
    {
        return  $this->subject('New Comment on Your Ticket – ' . $this->ticket->id)->markdown('emails.comment-add')->with(['ticket' => $this->ticket,'notifiable' => $this->notifiable,'comment' => $this->comment ]);
    }
}
