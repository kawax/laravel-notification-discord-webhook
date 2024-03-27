<?php

namespace Revolution\Laravel\Notification\DiscordWebhook;

use Illuminate\Support\Arr;

class DiscordMessage
{
    protected array $options = [];

    public function __construct(
        protected ?string $content = null,
        protected ?array  $embeds = null,
    )
    {
        //
    }

    public static function create(
        ?string $content = null,
        ?array  $embeds = null,
    ): static
    {
        return new static(content: $content, embeds: $embeds);
    }

    public function content(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function embeds(array $embeds): static
    {
        $this->embeds = $embeds;

        return $this;
    }

    public function with(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function isValid(): bool
    {
        if (filled($this->content) || filled($this->embeds || Arr::has($this->options, 'components'))) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return collect(
            [
                'content' => $this->content,
                'embeds' => $this->embeds,
            ],
        )->merge($this->options)
            ->reject(fn ($item) => blank($item))
            ->toArray();
    }
}
