<?php

namespace App\Notifications;

use App\Mail\Escalation;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class TicketEscalated extends Notification
{
    use Queueable, SerializesModels;

    public array $user;

    public function __construct(array $user)
    {
        $this->user = $user;
    }

    public function via(object $notifiable): array
    {
        // database + email + custom FCM channel
        return ['database', 'mail', 'fcm'];
    }

    public function toMail(object $notifiable): Escalation
    {
        $ticket = $this->ticket();

        return (new Escalation($ticket, $notifiable))
            ->to($notifiable->email);
    }

    public function toArray(object $notifiable): array
    {
        $ticket   = $this->ticket();
        $ticketId = $ticket?->id ?? $this->user['ticket_id'] ?? null;

        $data = [
            'user_id'    => $this->user['id'] ?? null,
            'message'    => 'Ticket ' . ($ticket?->ticketsubtype?->name ?? '#'.$ticketId) . ' has been escalated.',
            'action_url' => $this->ticketUrl($ticketId),
            'created_at' => now()->toDateTimeString(),
            'type'       => 'ticket_escalated',
            'ticket_id'  => $ticketId,
        ];

        return [
            'encrypted_data' => Crypt::encryptString(
                json_encode($data, JSON_UNESCAPED_UNICODE)
            ),
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $ticket = $this->ticket();
        $id     = $ticket?->id ?? ($this->user['ticket_id'] ?? null);
        $name   = $ticket?->ticketsubtype?->name;

        $title = 'Ticket escalated';
        $body  = $name
            ? "Ticket #{$id} ({$name}) has been escalated."
            : "Ticket #{$id} has been escalated.";

        $payload = [
            'title' => $title,
            'body'  => $body,
            'data'  => [
                'title'          => $title,
                'body'           => $body,
                'type'           => 'ticket_escalated',
                'ticket_id'      => (string) ($id ?? ''),
                'ticket_subtype' => (string) ($name ?? ''),
                'action_url'     => $this->ticketUrl($id),
                'bigPicture'     => null,
            ],
        ];

        Log::info('TicketEscalated@toFcm payload', [
            'user_id'       => $this->user['id'] ?? null,
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
        $tid = $this->user['ticket_id'] ?? null;
        if (!$tid) {
            return null;
        }

        return Ticket::with('ticketsubtype')->find($tid);
    }

    protected function ticketUrl($id): string
    {
        if (!$id) {
            return url('/tickets_view');
        }

        try {
            return route('ticket.view', ['id' => $id]);
        } catch (\Throwable $e) {
            return url("/tickets_view/{$id}");
        }
    }
}
