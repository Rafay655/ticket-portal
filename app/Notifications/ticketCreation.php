<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Services\FcmClient;  // Add the FcmClient service
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class ticketCreation extends Notification
{
    use Queueable;

    public function __construct(public array $user) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'fcm'];  // FCM added for sending push notification
    }

    public function toMail(object $notifiable)
    {
        $ticket = Ticket::with('ticketsubtype')->find($this->user['ticket_id']);
        return (new \App\Mail\TicketCreated($ticket, $notifiable))
            ->to($notifiable->email);
    }

    public function toArray(object $notifiable): array
    {
        $ticket = Ticket::with('ticketsubtype', 'tickettype', 'requestedby', 'assignto')
            ->find($this->user['ticket_id']);

        $subtypeName = optional($ticket->ticketsubtype)->name ?? 'ticket';

        $data = [
            'user_id'    => $this->user['id'],
            'message'    => 'created a new ticket ' . $subtypeName . '.',
            'action_url' => route('ticket.view', ['id' => $this->user['ticket_id']]),
            'created_at' => now(),
            'type'       => 'ticket_created',
            'ticket_id'  => $ticket?->id,
        ];

        return ['encrypted_data' => Crypt::encryptString(json_encode($data))];
    }

    public function toFcm(object $notifiable): array
    {
        $ticket = Ticket::with('ticketsubtype', 'tickettype', 'requestedby')
            ->find($this->user['ticket_id']);

        $subtypeName = optional($ticket?->ticketsubtype)->name ?? 'ticket';

        $title = 'New ticket assigned';
        $body  = sprintf(
            'Ticket #%s (%s) has been created and assigned to you.',
            $ticket?->id ?? '-',
            $subtypeName
        );

        return [
            'title' => $title,
            'body'  => $body,

            // 👇 This "data" array goes directly into FCM data payload
            'data'  => [
                'title'         => $title,
                'body'          => $body,

                'ticket_id'     => (string) ($ticket?->id ?? ''),
                'ticket_subtype'=> $subtypeName,
                'ticket_type'   => optional($ticket?->tickettype)->name,
                'requested_by'  => optional($ticket?->requestedby)->name,
                'action_url'    => route('ticket.view', ['id' => $ticket?->id]),

                // For your mobile app’s logic
                'type'          => 'ticket_created',

                // Optional visual fields for the app
                'bigPicture'    => null, // or some URL if you have one
                // 'profilePicture' => $notifiable->avatar_url ?? null, // if you want to override default
            ],
        ];
    }
}

