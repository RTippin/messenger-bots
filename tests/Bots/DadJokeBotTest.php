<?php

namespace RTippin\MessengerBots\Tests\Bots;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ClientEvents\Typing;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\MessengerBots\Bots\DadJokeBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class DadJokeBotTest extends MessengerBotsTestCase
{
    const RESPONSE = ['joke' => 'Dad joke.'];
    const PARAMS = [
        'handler' => 'dad_joke',
        'match' => 'exact',
        'cooldown' => 0,
        'admin_only' => false,
        'enabled' => true,
        'triggers' => ['!dadjoke'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([DadJokeBot::class]);
    }

    /** @test */
    public function it_is_registered()
    {
        $this->assertTrue(MessengerBots::isValidHandler(DadJokeBot::class));
    }

    /** @test */
    public function it_gets_handler_dto()
    {
        $expected = [
            'alias' => 'dad_joke',
            'description' => 'Get a random dad joke.',
            'name' => 'Dad Joke',
            'unique' => true,
            'authorize' => false,
            'triggers' => null,
            'match' => null,
        ];

        $this->assertSame($expected, DadJokeBot::getDTO()->toArray());
    }

    /** @test */
    public function it_passes_resolving_params()
    {
        $this->assertInstanceOf(ResolvedBotHandlerDTO::class, DadJokeBot::testResolve(self::PARAMS));
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
        ]), self::PARAMS)
            ->assertSuccessful();
    }

    /** @test */
    public function it_gets_response_and_stores_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            DadJokeBot::API_ENDPOINT => Http::response(self::RESPONSE),
        ]);
        $dad = MessengerBots::initializeHandler(DadJokeBot::class)
            ->setDataForHandler($thread, $action, $message);

        $dad->handle();

        $this->assertDatabaseHas('messages', [
            'body' => ':man: Dad joke.',
            'owner_type' => 'bots',
        ]);
        $this->assertFalse($dad->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_when_http_fails()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            DadJokeBot::API_ENDPOINT => Http::response([], 400),
        ]);
        $dad = MessengerBots::initializeHandler(DadJokeBot::class)
            ->setDataForHandler($thread, $action, $message);

        $dad->handle();

        $this->assertTrue($dad->shouldReleaseCooldown());
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
            DadJokeBot::API_ENDPOINT => Http::response(self::RESPONSE),
        ]);

        MessengerBots::initializeHandler(DadJokeBot::class)
            ->setDataForHandler($thread, $action, $message)
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }
}
