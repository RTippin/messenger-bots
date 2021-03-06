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
use RTippin\MessengerBots\Bots\InsultBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class InsultBotTest extends MessengerBotsTestCase
{
    const RESPONSE = ['insult' => 'You suck!'];
    const PARAMS = [
        'handler' => 'insult',
        'match' => 'exact',
        'cooldown' => 0,
        'admin_only' => false,
        'enabled' => true,
        'triggers' => ['!insult'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([InsultBot::class]);
    }

    /** @test */
    public function it_is_registered()
    {
        $this->assertTrue(MessengerBots::isValidHandler(InsultBot::class));
    }

    /** @test */
    public function it_gets_handler_dto()
    {
        $expected = [
            'alias' => 'insult',
            'description' => 'Responds with a random insult.',
            'name' => 'Insult',
            'unique' => true,
            'authorize' => false,
            'triggers' => null,
            'match' => null,
        ];

        $this->assertSame($expected, InsultBot::getDTO()->toArray());
    }

    /** @test */
    public function it_passes_resolving_params()
    {
        $this->assertInstanceOf(ResolvedBotHandlerDTO::class, InsultBot::testResolve(self::PARAMS));
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
            InsultBot::API_ENDPOINT => Http::response(self::RESPONSE),
        ]);
        $insult = MessengerBots::initializeHandler(InsultBot::class)
            ->setDataForHandler($thread, $action, $message);

        $insult->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Richard Tippin, You suck!',
        ]);
        $this->assertFalse($insult->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_when_http_fails()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            InsultBot::API_ENDPOINT => Http::response([], 400),
        ]);
        $insult = MessengerBots::initializeHandler(InsultBot::class)
            ->setDataForHandler($thread, $action, $message);

        $insult->handle();

        $this->assertTrue($insult->shouldReleaseCooldown());
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
            InsultBot::API_ENDPOINT => Http::response(self::RESPONSE),
        ]);

        MessengerBots::initializeHandler(InsultBot::class)
            ->setDataForHandler($thread, $action, $message)
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }
}
