<?php

namespace App\Exceptions;

use App\Models\Ticket;
use Illuminate\Http\Request;
use RuntimeException;

class ActiveTicketAlreadyExists extends RuntimeException
{
    public function __construct(public Ticket $ticket)
    {
        parent::__construct("You already have an open or in-progress ticket for this Ticket Sub-Category (ticket #{$ticket->id}). Please continue on the existing ticket instead of creating a new one.");
    }

    public function report(): bool
    {
        return false;
    }

    public function render(Request $request)
    {
        $redirectUrl = route('ticket.view', [
            'id' => $this->ticket->id,
            'return_to' => route('tickets.index'),
        ]);

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'status' => false,
                'code' => 'duplicate_active_ticket',
                'message' => $this->getMessage(),
                'existing_ticket_id' => $this->ticket->id,
                'existing_ticket_status' => $this->ticket->status,
                'existing_ticket_url' => url('/api/tickets/ticket/'.$this->ticket->id),
                'redirect_url' => $redirectUrl,
            ], 409);
        }

        return redirect($redirectUrl)
            ->with('error', $this->getMessage())
            ->with('duplicate_ticket_id', $this->ticket->id);
    }
}
