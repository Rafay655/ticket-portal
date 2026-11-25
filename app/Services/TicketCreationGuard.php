<?php

namespace App\Services;

use App\Exceptions\ActiveTicketAlreadyExists;
use App\Models\Ticket;
use App\Models\TicketTimeline;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TicketCreationGuard
{
    public function assertCanCreate(int $requesterId, $subtypeId): void
    {
        // A missing subtype is not a match for any named subtype.
        if ($subtypeId === null || $subtypeId === '') {
            return;
        }

        $existing = Ticket::where('requested_by', $requesterId)
            ->where('ticketsubtype_id', $subtypeId)
            ->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(TRIM(status), '_', ''), ' ', ''), '-', '')) IN (?, ?)", ['open', 'inprogress'])
            ->oldest('id')
            ->first();

        if ($existing) {
            throw new ActiveTicketAlreadyExists($existing);
        }
    }

    public function create(array $input): Ticket
    {
        return DB::transaction(function () use ($input) {
            // Both web and API creation lock the same requester row, even when
            // there is no ticket yet, to serialize simultaneous submissions.
            User::whereKey($input['requested_by'])->lockForUpdate()->firstOrFail();
            $this->assertCanCreate((int) $input['requested_by'], $input['ticketsubtype_id'] ?? null);

            $ticket = Ticket::create($input);
            TicketTimeline::create([
                'ticket_id' => $ticket->id,
                'assign_to' => $input['assigned_to'],
                'assign_time' => now(),
                'assign_type' => 'By Create',
            ]);

            return $ticket;
        }, 3);
    }
}
