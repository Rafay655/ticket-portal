<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Notifications\Channels\FcmChannel;

class DummyFcm extends Notification
{
    // Keep it sync (no ShouldQueue) so it fires immediately
   public function via(object $notifiable): array
{
    return ['fcm']; // not FcmChannel::class
}

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Hello from FCM 👋',
            'body'  => 'This is a test push sent via your custom channel.',
            'image' => null,
            'data'  => [
                'type'       => 'dummy_test',
                'action_url' => 'https://example.com/notifications/test',
            ],
        ];
    }
}
