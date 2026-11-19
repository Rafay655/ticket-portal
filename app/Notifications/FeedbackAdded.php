<?php

namespace App\Notifications;

use App\Mail\FeedbackTicket;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class FeedbackAdded extends Notification
{
    use Queueable, SerializesModels;

    public array $user;
    public ?string $comment;

    public function __construct($user, $comment = null)
    {
        $this->user = (array) $user;

        // Normalize comment (array|string|null -> string|null)
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
        return ['database', 'mail', 'fcm'];
    }

    public function toMail($notifiable): FeedbackTicket
    {
        $ticket = $this->ticket();

        return (new FeedbackTicket($ticket, $notifiable, $this->comment))
            ->to($notifiable->email);
    }

    public function toArray($notifiable): array
    {
        $ticket   = $this->ticket();
        $ticketId = $ticket?->id ?? $this->user['ticket_id'] ?? null;

        $data = [
            'user_id'    => $this->user['id'] ?? null,
            'message'    => 'added feedback on ' . ($ticket?->ticketsubtype?->name ?? '#'.$ticketId) . ' ticket.',
            'action_url' => $this->ticketUrl($ticketId),
            'created_at' => now()->toDateTimeString(),
            'type'       => 'ticket_feedback_added',
            'ticket_id'  => $ticketId,
            'comment'    => (string) ($this->comment ?? ''),
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

        $title = 'New feedback added';
        $body  = $name
            ? "Feedback added on Ticket #{$id} ({$name})."
            : "Feedback added on Ticket #{$id}.";

        $payload = [
            'title' => $title,
            'body'  => $body,
            'data'  => [
                'title'          => $title,
                'body'           => $body,
                'type'           => 'ticket_feedback_added',
                'ticket_id'      => (string) $id,
                'ticket_subtype' => (string) ($name ?? ''),
                'action_url'     => $this->ticketUrl($id),
                'comment'        => (string) ($this->comment ?? ''),
                'bigPicture'     => null,
            ],
        ];

        Log::info('FeedbackAdded@toFcm payload', [
            'user_id'       => $this->user['id'] ?? null,
            'notifiable_id' => $notifiable->id ?? null,
            'fcm_route'     => method_exists($notifiable, 'routeNotificationForFcm')
                ? $notifiable->routeNotificationForFcm()
                : null,
            'payload'       => $payload,
        ]);

        return $payload;
    }

    /* ----------------- helpers ----------------- */

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
            return url('/tickets_view'); // minimal fallback
        }

        try {
            return route('ticket.view', ['id' => $id]);
        } catch (\Throwable $e) {
            return url("/tickets_view/{$id}");
        }
    }
}
