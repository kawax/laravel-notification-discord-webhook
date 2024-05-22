<?php

namespace Revolution\Laravel\Notification\DiscordWebhook;

use Illuminate\Http\Client\PendingRequest;
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

        Http::attach(
            name: 'payload_json',
            contents: $message->toJson(),
            headers: ['Content-Type' => 'application/json']
        )->when(filled($message->getAttachments()), function (PendingRequest $client) use ($message) {
            foreach ($message->getAttachments() as $id => $attach) {
                $client->attach(
                    name: "files[$id]",
                    contents: $attach->content,
                    filename: $attach->filename,
                    headers: ['Content-Type' => $attach->filetype]
                );
            }
        })->post($webhook_url)->throw();
    }
}
