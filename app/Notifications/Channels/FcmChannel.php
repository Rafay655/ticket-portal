<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Services\FcmClient;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    protected FcmClient $fcm;

    public function __construct(FcmClient $fcm)
    {
        $this->fcm = $fcm;
    }

    public function send($notifiable, Notification $notification): void
    {
       
        $tokens = method_exists($notifiable, 'routeNotificationForFcm')
            ? (array) $notifiable->routeNotificationForFcm()
            : [];

        if (empty($tokens)) {
            Log::warning('⚠️ FCM: No tokens found for notifiable', [
                'notifiable_id' => $notifiable->id ?? null,
            ]);
            return;
        }

        if (!method_exists($notification, 'toFcm')) {
            Log::warning('⚠️ FCM: Notification missing toFcm() method');
            return;
        }

        $msg = $notification->toFcm($notifiable);

        Log::info('Payload for FCM', $msg);

        try {
           
            foreach ($tokens as $token) {
                $this->fcm->sendToToken($token, $msg);
            }
        } catch (\Throwable $e) {
            Log::error('❌ FCM: Failed to send message', [
                'error' => $e->getMessage(),
                'payload' => $msg,
            ]);
        }
    }
}
