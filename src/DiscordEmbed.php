<?php

namespace Revolution\Laravel\Notification\DiscordWebhook;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

final class DiscordEmbed implements Arrayable
{
    protected array $options = [];

    public function __construct(
        protected ?string $title = null,
        protected ?string $description = null,
        protected ?string $url = null,
        protected ?string $image = null,
        protected ?string $thumbnail = null,
    )
    {
        //
    }

    public static function make(
        ?string $title = null,
        ?string $description = null,
        ?string $url = null,
        ?string $image = null,
        ?string $thumbnail = null,
    ): self
    {
        return new self(...func_get_args());
    }

    public function with(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function toArray(): array
    {
        return collect([
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
        ])->when(filled($this->image), function (Collection $collection) {
            $collection->put('image', [
                'url' => $this->image,
            ]);
        })->when(filled($this->thumbnail), function (Collection $collection) {
            $collection->put('thumbnail', [
                'url' => $this->thumbnail,
            ]);
        })->merge($this->options)
            ->reject(fn ($item) => blank($item))
            ->toArray();
    }
}
