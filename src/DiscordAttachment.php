<?php

namespace Revolution\Laravel\Notification\DiscordWebhook;

class DiscordAttachment
{
    public function __construct(
        readonly public string $content,
        readonly public string $filename,
        readonly public string $description = '',
        readonly public string $filetype = '',
    ) {
        //
    }

    public static function make(
        string $content,
        string $filename,
        string $description = '',
        string $filetype = '',
    ): static {
        return new static(...func_get_args());
    }
}
