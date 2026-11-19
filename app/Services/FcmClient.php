<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\AndroidConfig;
use Illuminate\Support\Facades\Log;

class FcmClient
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(
    config('firebase.credentials.file')
);
        $this->messaging = $factory->createMessaging();
    }

    
    public function sendToToken(string $token, array $payload): array
    {
        try {
            // Extract high-level fields
            $title = $payload['title'] ?? '';
            $body  = $payload['body'] ?? '';
            $data  = $payload['data'] ?? [];

            // Final data that will go in FCM "data" section
            $payloadData = array_merge([
                'title'         => $title,
                'body'          => $body,
                'bigPicture'    => $data['bigPicture']    ?? null,
                'profilePicture'=> $data['profilePicture'] ?? 'http://testticket.myflexihr.com/public/images/dummy.png',
            ], $data);

            // APNs (iOS) config – content-available and mutable-content only
            $apnsConfig = ApnsConfig::fromArray([
                'payload' => [
                    'aps' => [
                        'content-available' => 1,
                        'mutable-content'   => 1,
                    ],
                ],
            ]);

            // Android config
            $androidConfig = AndroidConfig::fromArray([
                'priority' => 'high',
            ]);

            // NOTE: no ->withNotification(), only data
            $message = CloudMessage::withTarget('token', $token)
                ->withApnsConfig($apnsConfig)
                ->withAndroidConfig($androidConfig)
                ->withData($payloadData);

            $response = $this->messaging->send($message);

            Log::info('✅ FCM sent', [
                'token'    => substr($token, -8),
                'response' => $response,
                'data'     => $payloadData,
            ]);

            return [
                'success'  => true,
                'response' => $response,
            ];

        } catch (\Throwable $e) {
            Log::error('❌ FCM failed', [
                'error' => $e->getMessage(),
                'token' => substr($token, -8),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }
}
