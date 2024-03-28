<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordChannel;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordMessage;
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
            return $request['content'] === 'test';
        });
    }

    public function test_notification_failed()
    {
        Http::fake([
            '*' => Http::response('', 500),
        ]);

        $this->expectException(RequestException::class);

        Notification::route('discord-webhook', config('services.discord.webhook'))
            ->notify(new TestNotification('test'));

        Http::assertSentCount(1);
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

    public function test_user_notify()
    {
        Http::fake();

        $user = new TestUser();

        $user->notify(new TestNotification('test'));

        Http::assertSentCount(1);
    }
}

class TestNotification extends \Illuminate\Notifications\Notification
{
    public function __construct(
        protected string $content,
    )
    {
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

class TestUser extends Model
{
    use Notifiable;

    public function routeNotificationForDiscordWebhook($notification): string
    {
        return config('services.discord.webhook');
    }
}
