<?php

namespace Revolution\Laravel\Notification\DiscordWebhook;

use Illuminate\Http\Client\RequestException;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class DiscordChannel
{
    /**
     * @throws RequestException
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        /**
         * @var DiscordMessage $message
         */
        $message = $notification->toDiscordWebhook($notifiable);

        if (! $message->isValid()) {
            return;
        }

        /**
         * @var string $webhook_url
         */
        $webhook_url = $notifiable->routeNotificationFor('discord-webhook');

        if (empty($webhook_url)) {
            return;
        }

        Http::post($webhook_url, $message->toArray())->throw();
    }
}
