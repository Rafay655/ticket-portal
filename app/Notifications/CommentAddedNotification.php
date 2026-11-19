<?php

namespace App\Notifications;

use App\Mail\CommentAdded;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class CommentAddedNotification extends Notification
{
    use Queueable;

    public array $userData;

    public function __construct($userData)
    {
        $this->userData = (array) $userData;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'fcm'];
    }

    public function toMail(object $notifiable): CommentAdded
    {
        Log::info('CommentAddedNotification@toMail triggered', [
            'ticket_id' => $this->userData['ticket_id'] ?? null,
        ]);

        $ticket  = Ticket::with('ticketsubtype')->find($this->userData['ticket_id']);
        $comment = $this->userData['comment'] ?? null;

        return (new CommentAdded($ticket, $notifiable, $comment))
            ->to($notifiable->email);
    }

    public function toArray(object $notifiable): array
    {
        Log::info('CommentAddedNotification@toArray called', [
            'ticket_id' => $this->userData['ticket_id'] ?? null,
        ]);

        $ticket = Ticket::with('ticketsubtype')->find($this->userData['ticket_id']);

        // Safely encode comment and any array-type values
        $commentValue = $this->userData['comment'] ?? '';
        if (is_array($commentValue)) {
            $commentValue = json_encode($commentValue, JSON_UNESCAPED_UNICODE);
        }

        $data = [
            'user_id'    => $this->userData['id'] ?? null,
            'message'    => 'commented on ' . ($ticket?->ticketsubtype?->name ?? '#'.$this->userData['ticket_id']),
            'action_url' => $this->ticketUrl($this->userData['ticket_id'] ?? null),
            'created_at' => now()->toDateTimeString(),
            'type'       => 'ticket_comment_added',
            'ticket_id'  => $ticket?->id ?? $this->userData['ticket_id'],
            'comment'    => $commentValue,
        ];

        return [
            'encrypted_data' => Crypt::encryptString(json_encode($data, JSON_UNESCAPED_UNICODE)),
        ];
    }

    public function toFcm(object $notifiable): array
    {
        Log::info('CommentAddedNotification@toFcm triggered', [
            'ticket_id' => $this->userData['ticket_id'] ?? null,
        ]);

        $ticket = $this->ticket();
        $id     = $ticket?->id ?? ($this->userData['ticket_id'] ?? null);
        $name   = $ticket?->ticketsubtype?->name;

        // comment text (safe, handle array)
        $commentValue = $this->userData['comment'] ?? '';
        if (is_array($commentValue)) {
            $commentValue = json_encode($commentValue, JSON_UNESCAPED_UNICODE);
        }

        $title = 'New comment on ticket';
        $body  = $name
            ? "New comment on Ticket #{$id} ({$name})."
            : "New comment on Ticket #{$id}.";

        return [
            'title' => $title,
            'body'  => $body,

            // 👇 This will become the FCM `data` payload
            'data'  => [
                'title'          => $title,
                'body'           => $body,

                'ticket_id'      => (string) ($id ?? ''),
                'ticket_subtype' => $name,
                'type'           => 'ticket_comment_added',
                'comment'        => (string) $commentValue,
                'action_url'     => $this->ticketUrl($id),

                // Optional visual / extra fields for the app
                'bigPicture'     => null,
                // 'profilePicture' => $notifiable->avatar_url ?? null,
            ],
        ];
    }

    /* ----------------- Helpers ----------------- */

    protected function ticket(): ?Ticket
    {
        $tid = $this->userData['ticket_id'] ?? null;
        if (!$tid) return null;

        return Ticket::with('ticketsubtype')->find($tid);
    }

    protected function ticketUrl($id): string
    {
        if (!$id) {
            return url('/tickets_view'); // Minimal fallback URL
        }

        try {
            return route('ticket.view', ['id' => $id]);
        } catch (\Throwable $e) {
            return url("/tickets_view/{$id}");
        }
    }
}
