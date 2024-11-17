<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordAttachment;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordChannel;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordMessage;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordEmbed;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    public function test_notification()
    {
        Http::fake();

        Notification::route('discord-webhook', config('services.discord.webhook'))
            ->notify(new TestNotification(content: 'test'));

        Http::assertSentCount(1);

        Http::assertSent(function (Request $request) {
            return $request->isMultipart()
                && $request[0]['name'] === 'payload_json'
                && $request[0]['contents'] === '{"content":"test"}'
                && empty($request[1]);
        });
    }

    public function test_notification_failed()
    {
        Event::fake();

        Http::fake([
            '*' => Http::response('', 500),
        ]);

        Notification::route('discord-webhook', config('services.discord.webhook'))
            ->notify(new TestNotification('test'));

        Http::assertSentCount(1);

        Event::assertDispatched(function (NotificationSent $event) {
            return $event->response->failed();
        });
    }

    public function test_notification_fake()
    {
        Notification::fake();

        Notification::route('discord-webhook', config('services.discord.webhook'))
            ->notify(new TestNotification('test'));

        Notification::assertSentOnDemand(TestNotification::class);
    }

    public function test_message()
    {
        $m = (new DiscordMessage(content: 'test'))
            ->embeds([['embeds' => 'test']])
            ->with(['with' => 'test']);

        $this->assertSame(['content' => 'test', 'embeds' => [['embeds' => 'test']], 'with' => 'test'], $m->toArray());
        $this->assertJson(json_encode(['content' => 'test', 'embeds' => [['embeds' => 'test']], 'with' => 'test']), $m->toJson());
    }

    public function test_message_embeds()
    {
        $m = new DiscordMessage(embeds: [['embeds' => 'test']]);

        $this->assertSame(['embeds' => [['embeds' => 'test']]], $m->toArray());
    }

    public function test_message_with()
    {
        $m = (new DiscordMessage())->with(['with' => 'test']);

        $this->assertSame(['with' => 'test'], $m->toArray());
    }

    public function test_message_valid()
    {
        $this->assertTrue(DiscordMessage::create(content: 'test', embeds: [])->isValid());

        $this->assertTrue(DiscordMessage::create(content: '')->embeds([])->with(['components' => ['test']])->isValid());
    }

    public function test_message_invalid()
    {
        $this->assertFalse(DiscordMessage::create()->isValid());

        $this->assertFalse(DiscordMessage::create(content: '', embeds: [])->with(['test' => 'test'])->isValid());
    }

    public function test_notification_attachments()
    {
        Http::fake();

        Notification::route('discord-webhook', config('services.discord.webhook'))
            ->notify(new TestFileNotification(content: 'test'));

        Http::assertSentCount(1);

        Http::assertSent(function (Request $request) {
            return $request->isMultipart()
                && $request[0]['name'] === 'payload_json'
                && Str::contains($request[0]['contents'], 'test')
                && $request[1]['name'] === 'files[0]'
                && $request[1]['filename'] === 'test';
        });
    }

    public function test_message_attachments()
    {
        Storage::fake('discord');

        $m = DiscordMessage::create()
            ->file(DiscordAttachment::make(content: UploadedFile::fake()->image('test')->get(), filename: 'test', description: 'test', filetype: 'image/jpeg'))
            ->file(new DiscordAttachment(content: UploadedFile::fake()->image('test2')->get(), filename: 'test2', description: 'test2', filetype: 'image/jpeg'));

        $this->assertIsArray($m->getAttachments());
        $this->assertCount(2, $m->getAttachments());
        $this->assertTrue($m->isValid());
    }

    public function test_user_notify()
    {
        Http::fake();

        $user = new TestUser();

        $user->notify(new TestNotification('test'));

        Http::assertSentCount(1);
    }

    public function test_embed()
    {
        $embed = DiscordEmbed::make(
            title: 'title',
            description: 'description',
            url: 'url',
            image: 'image',
            thumbnail: 'thumbnail',
        )->with(['color' => 'color']);

        $this->assertSame(['title' => 'title', 'description' => 'description', 'url' => 'url', 'image' => ['url' => 'image'], 'thumbnail' => ['url' => 'thumbnail'], 'color' => 'color'], $embed->toArray());
    }

    public function test_message_embed()
    {
        $embed = DiscordEmbed::make(
            title: 'title',
        );

        $m = DiscordMessage::create()
            ->embed($embed);

        $this->assertSame(['title' => 'title'], $m->toArray()['embeds'][0]);
    }
}

class TestNotification extends \Illuminate\Notifications\Notification
{
    public function __construct(
        protected string $content,
    ) {
    }

    public function via(object $notifiable): array
    {
        return [DiscordChannel::class];
    }

    public function toDiscordWebhook(object $notifiable): DiscordMessage
    {
        return DiscordMessage::create(content: $this->content)
            ->content($this->content)
            ->embeds([])
            ->with([]);
    }
}

class TestFileNotification extends \Illuminate\Notifications\Notification
{
    public function __construct(
        protected string $content,
    ) {
    }

    public function via(object $notifiable): array
    {
        return [DiscordChannel::class];
    }

    public function toDiscordWebhook(object $notifiable): DiscordMessage
    {
        return DiscordMessage::create(content: $this->content)
            ->file(DiscordAttachment::make(content: UploadedFile::fake()->image('test')->get(), filename: 'test', description: 'test', filetype: 'image/jpeg'));
    }
}

class TestUser extends Model
{
    use Notifiable;

    public function routeNotificationForDiscordWebhook($notification): string
    {
        return config('services.discord.webhook');
    }
}
