<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class ResignTicket extends Notification 
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct($data)
    {
        $this->data = (array) $data;
    }

    public function via(object $notifiable): array
    {
        // Database, mail, and custom FCM channel
        return ['database', 'mail', 'fcm'];
    }

    public function toMail(object $notifiable)
    {
        $ticket = $this->ticket();

        // Ensure you have the App\Mail\Resigning mailable
        return (new \App\Mail\Resigning($ticket, $notifiable))
            ->to($notifiable->email);
    }

    public function toArray(object $notifiable): array
    {
        $ticket = $this->ticket();
        $tid    = $ticket?->id ?? ($this->data['ticket_id'] ?? null);
        $name   = $ticket?->ticketsubtype?->name;

        $payload = [
            'user_id'    => $this->data['id'] ?? null,
            'message'    => 'reassigned ticket ' . ($name ?? '#'.$tid) . ' to you.',
            'action_url' => $this->ticketUrl($tid),
            'created_at' => now()->toDateTimeString(),
            'type'       => 'ticket_reassigned', // keep type consistent
            'ticket_id'  => $tid,
        ];

        return [
            'encrypted_data' => Crypt::encryptString(
                json_encode($payload, JSON_UNESCAPED_UNICODE)
            ),
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $ticket = $this->ticket();
        $id     = $ticket?->id ?? ($this->data['ticket_id'] ?? null);
        $name   = $ticket?->ticketsubtype?->name;

        $title = 'Ticket reassigned';
        $body  = $name
            ? "Ticket #{$id} ({$name}) has been reassigned to you."
            : "Ticket #{$id} has been reassigned to you.";

        $payload = [
            'title' => $title,
            'body'  => $body,

            // This goes into FCM "data" field in FcmClient
            'data'  => [
                'title'          => $title,
                'body'           => $body,
                'type'           => 'ticket_reassigned',
                'ticket_id'      => (string) ($id ?? ''),
                'ticket_subtype' => (string) ($name ?? ''),
                'action_url'     => $this->ticketUrl($id),
                'bigPicture'     => null,
            ],
        ];

        Log::info('ResignTicket@toFcm payload', [
            'user_id'       => $this->data['id'] ?? null,
            'notifiable_id' => $notifiable->id ?? null,
            'fcm_route'     => method_exists($notifiable, 'routeNotificationForFcm')
                ? $notifiable->routeNotificationForFcm()
                : null,
            'payload'       => $payload,
        ]);

        return $payload;
    }

    /* ----------------- Helpers ----------------- */

    protected function ticket(): ?Ticket
    {
        $tid = $this->data['ticket_id'] ?? null;
        if (!$tid) return null;

        return Ticket::with('ticketsubtype', 'tickettype', 'requestedby', 'assignto')
            ->find($tid);
    }

    protected function ticketUrl($id): string
    {
        if (!$id) return url('/tickets_view');

        try {
            return route('ticket.view', ['id' => $id]);
        } catch (\Throwable $e) {
            return url("/tickets_view/{$id}");
        }
    }
}
