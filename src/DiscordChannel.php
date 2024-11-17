<?php

namespace Revolution\Laravel\Notification\DiscordWebhook;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class DiscordChannel
{
    /**
     * @throws RequestException
     */
    public function send(mixed $notifiable, Notification $notification): ?Response
    {
        /**
         * @var DiscordMessage $message
         */
        $message = $notification->toDiscordWebhook($notifiable);

        if (! $message->isValid()) {
            return null;
        }

        /**
         * @var string $webhook_url
         */
        $webhook_url = $notifiable->routeNotificationFor('discord-webhook');

        if (empty($webhook_url)) {
            return null;
        }

        return Http::attach(
            name: 'payload_json',
            contents: $message->toJson(),
            headers: ['Content-Type' => 'application/json'],
        )->when(filled($message->getAttachments()), function (PendingRequest $client) use ($message) {
            foreach ($message->getAttachments() as $id => $attach) {
                $client->attach(
                    name: "files[$id]",
                    contents: $attach->content,
                    filename: $attach->filename,
                    headers: ['Content-Type' => $attach->filetype],
                );
            }
        })->post($webhook_url);
    }
}
