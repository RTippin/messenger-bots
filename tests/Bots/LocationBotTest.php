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
use RTippin\MessengerBots\Bots\LocationBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class LocationBotTest extends MessengerBotsTestCase
{
    const RESPONSE = [
        'status' => 'success',
        'city' => 'City',
        'regionName' => 'Region',
        'country' => 'Country',
    ];
    const PARAMS = [
        'handler' => 'location',
        'cooldown' => 0,
        'admin_only' => false,
        'enabled' => true,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([LocationBot::class]);
    }

    /** @test */
    public function it_is_registered()
    {
        $this->assertTrue(MessengerBots::isValidHandler(LocationBot::class));
    }

    /** @test */
    public function it_gets_handler_dto()
    {
        $expected = [
            'alias' => 'location',
            'description' => 'Get the general location of the message sender.',
            'name' => 'Locator',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!location', '!findMe', '!whereAmI'],
            'match' => 'exact:caseless',
        ];

        $this->assertSame($expected, LocationBot::getDTO()->toArray());
    }

    /** @test */
    public function it_passes_resolving_params()
    {
        $this->assertInstanceOf(ResolvedBotHandlerDTO::class, LocationBot::testResolve(self::PARAMS));
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
    public function it_gets_free_response_and_stores_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            LocationBot::API_ENDPOINT_FREE.'127.0.0.1*' => Http::response(self::RESPONSE),
        ]);
        $location = MessengerBots::initializeHandler(LocationBot::class)
            ->setDataForHandler($thread, $action, $message, null, false, '127.0.0.1');

        $location->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'My sources say you are coming all the way from City, Region, Country!',
            'reply_to_id' => $message->id,
        ]);
        $this->assertFalse($location->shouldReleaseCooldown());
    }

    /** @test */
    public function it_gets_pro_response_and_stores_message()
    {
        config()->set('messenger-bots.ip_api_key', 'IP-KEY');
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            LocationBot::API_ENDPOINT_PRO.'127.0.0.1*' => Http::response(self::RESPONSE),
        ]);
        $location = MessengerBots::initializeHandler(LocationBot::class)
            ->setDataForHandler($thread, $action, $message, null, false, '127.0.0.1');

        $location->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'My sources say you are coming all the way from City, Region, Country!',
            'reply_to_id' => $message->id,
        ]);
        $this->assertFalse($location->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_and_sends_error_message_if_http_fails()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            LocationBot::API_ENDPOINT_FREE.'127.0.0.1*' => Http::response([], 400),
        ]);
        $location = MessengerBots::initializeHandler(LocationBot::class)
            ->setDataForHandler($thread, $action, $message, null, false, '127.0.0.1');

        $location->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'It seems that I have no clue where you are right now!',
            'reply_to_id' => $message->id,
        ]);
        $this->assertTrue($location->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_and_sends_error_message_if_status_response_not_success()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            LocationBot::API_ENDPOINT_FREE.'127.0.0.1*' => Http::response(['status' => 'error']),
        ]);
        $location = MessengerBots::initializeHandler(LocationBot::class)
            ->setDataForHandler($thread, $action, $message, null, false, '127.0.0.1');

        $location->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'It seems that I have no clue where you are right now!',
            'reply_to_id' => $message->id,
        ]);
        $this->assertTrue($location->shouldReleaseCooldown());
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
            LocationBot::API_ENDPOINT_FREE.'127.0.0.1*' => Http::response(self::RESPONSE),
        ]);

        MessengerBots::initializeHandler(LocationBot::class)
            ->setDataForHandler($thread, $action, $message, null, false, '127.0.0.1')
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }
}
