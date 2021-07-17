<?php

namespace RTippin\MessengerBots\Tests\Bots;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ClientEvents\Typing;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\MessengerBots\Bots\YoMommaBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class YoMommaBotTest extends MessengerBotsTestCase
{
    const DATA = ['joke' => 'Yo momma ugly!'];

    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([YoMommaBot::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'yomomma',
            'description' => 'Get a random yo momma joke.',
            'name' => 'Yo Momma',
            'unique' => true,
            'authorize' => false,
            'triggers' => null,
            'match' => null,
        ];

        $this->assertSame($expected, MessengerBots::getHandlerSettings(YoMommaBot::class));
    }

    /** @test */
    public function it_can_be_attached_to_a_bot_handler()
    {
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'yomomma',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['!yomomma'],
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function it_gets_response_and_stores_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            YoMommaBot::API_ENDPOINT => Http::response(self::DATA),
        ]);
        $momma = MessengerBots::initializeHandler(YoMommaBot::class)
            ->setDataForMessage($thread, $action, $message);

        $momma->handle();

        $this->assertDatabaseHas('messages', [
            'body' => ':woman: Yo momma ugly!',
        ]);
        $this->assertFalse($momma->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_when_http_fails()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            YoMommaBot::API_ENDPOINT => Http::response([], 400),
        ]);
        $momma = MessengerBots::initializeHandler(YoMommaBot::class)
            ->setDataForMessage($thread, $action, $message);

        $momma->handle();

        $this->assertTrue($momma->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        Http::fake([
            YoMommaBot::API_ENDPOINT => Http::response(self::DATA),
        ]);

        MessengerBots::initializeHandler(YoMommaBot::class)
            ->setDataForMessage($thread, $action, $message)
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }
}
