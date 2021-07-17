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
use RTippin\MessengerBots\Bots\JokeBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class JokeBotTest extends MessengerBotsTestCase
{
    const DATA = [
        'setup' => 'Setup!',
        'punchline' => 'And punchline!',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([JokeBot::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'random_joke',
            'description' => 'Get a random joke. Has a setup and a punchline.',
            'name' => 'Jokester',
            'unique' => true,
            'authorize' => false,
            'triggers' => null,
            'match' => null,
        ];

        $this->assertSame($expected, MessengerBots::getHandlerSettings(JokeBot::class));
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
            'handler' => 'random_joke',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['!joke'],
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
            JokeBot::API_ENDPOINT => Http::response(self::DATA),
        ]);
        $joke = MessengerBots::initializeHandler(JokeBot::class)
            ->setDataForMessage($thread, $action, $message);

        $joke->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Setup!',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'And punchline!',
        ]);
        $this->assertFalse($joke->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_when_http_fails()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            JokeBot::API_ENDPOINT => Http::response([], 400),
        ]);
        $joke = MessengerBots::initializeHandler(JokeBot::class)
            ->setDataForMessage($thread, $action, $message);

        $joke->handle();

        $this->assertTrue($joke->shouldReleaseCooldown());
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
            JokeBot::API_ENDPOINT => Http::response(self::DATA),
        ]);

        MessengerBots::initializeHandler(JokeBot::class)
            ->setDataForMessage($thread, $action, $message)
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }
}
