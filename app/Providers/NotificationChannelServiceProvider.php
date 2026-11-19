<?php

namespace App\Providers;

use App\Notifications\Channels\FcmChannel;
use App\Services\FcmClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Notifications\ChannelManager;

class NotificationChannelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->make(ChannelManager::class)->extend('fcm', function ($app) {
            return new FcmChannel(
                $app->make(FcmClient::class)
            );
        });
    }
}
