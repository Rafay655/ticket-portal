<?php

namespace App\Notifications;

use App\Mail\CloseTicket;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class ticketClose extends Notification 
{
    use Queueable;

    public array $user;
    public ?string $comment;

    public function __construct(array $user, $comment = null)
    {
        $this->user = $user;

        // Normalize comment (handle array, string, null)
        if (is_array($comment)) {
            $this->comment = trim(
                implode("\n", array_filter(array_map('strval', $comment)))
            );
        } elseif ($comment !== null) {
            $this->comment = (string) $comment;
        } else {
            $this->comment = null;
        }
    }

    public function via($notifiable): array
    {
        // Database + Mail + custom FCM channel
        return ['database', 'mail', 'fcm'];
    }

    public function toMail($notifiable): CloseTicket
    {
        $ticket = $this->ticket();

        return (new CloseTicket($ticket, $notifiable, $this->comment))
            ->to($notifiable->email);
    }

    public function toArray($notifiable): array
    {
        $ticket   = $this->ticket();
        $ticketId = $ticket?->id ?? $this->user['ticket_id'] ?? null;

        $data = [
            'user_id'    => $this->user['id'] ?? null,
            'message'    => 'closed the ticket ' . ($ticket?->ticketsubtype?->name ?? '#'.$ticketId) . '.',
            'action_url' => $this->ticketUrl($ticketId),
            'created_at' => now()->toDateTimeString(),
            'type'       => 'ticket_closed',
            'ticket_id'  => $ticketId,
            'comment'    => $this->comment ?? '',
        ];

        return [
            'encrypted_data' => Crypt::encryptString(
                json_encode($data, JSON_UNESCAPED_UNICODE)
            ),
        ];
    }

    public function toFcm($notifiable): array
    {
        $ticket = $this->ticket();
        $id     = $ticket?->id ?? $this->user['ticket_id'] ?? null;
        $name   = $ticket?->ticketsubtype?->name;

        $title  = 'Ticket closed';
        $body   = $name
            ? "Ticket #{$id} ({$name}) has been closed."
            : "Ticket #{$id} has been closed.";

        $payload = [
            'title' => $title,
            'body'  => $body,

            // This will be passed as "data" to FCM via FcmClient
            'data'  => [
                'title'      => $title,
                'body'       => $body,
                'type'       => 'ticket_closed',
                'ticket_id'  => (string) ($id ?? ''),
                'ticket_subtype' => $name,
                'action_url' => $this->ticketUrl($id),
                'comment'    => $this->comment ?? '',
                'bigPicture'    => null,
                // 'profilePicture' => $notifiable->avatar_url ?? null, // if you have it
            ],
        ];

        Log::info('ticketClose@toFcm payload', [
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
            return url('/tickets_view'); // fallback
        }

        try {
            return route('ticket.view', ['id' => $id]);
        } catch (\Throwable $e) {
            return url("/tickets_view/{$id}");
        }
    }
}
