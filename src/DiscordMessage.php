<?php

namespace Revolution\Laravel\Notification\DiscordWebhook;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Conditionable;

class DiscordMessage implements Arrayable, Jsonable
{
    use Conditionable;

    /**
     * @var array<DiscordAttachment>
     */
    protected array $attachments = [];

    protected array $options = [];

    public function __construct(
        protected ?string $content = null,
        protected ?array $embeds = null,
    ) {
        //
    }

    public static function create(
        ?string $content = null,
        ?array $embeds = null,
    ): static {
        return new static(...func_get_args());
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

    public function file(DiscordAttachment $attachment): static
    {
        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * @internal
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function with(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function isValid(): bool
    {
        return Arr::hasAny($this->toArray(), ['content', 'embeds', 'components', 'attachments']);
    }

    public function toJson($options = 0): string
    {
        return collect($this->toArray())->toJson($options);
    }

    public function toArray(): array
    {
        $attachments = collect($this->getAttachments())
            ->map(fn (DiscordAttachment $attachment, int $id) => [
                'id' => $id,
                'description' => $attachment->description,
                'filename' => $attachment->filename,
            ])->toArray();

        return collect([
            'content' => $this->content,
            'embeds' => $this->embeds,
            'attachments' => $attachments,
        ])->merge($this->options)
            ->reject(fn ($item) => blank($item))
            ->toArray();
    }
}
