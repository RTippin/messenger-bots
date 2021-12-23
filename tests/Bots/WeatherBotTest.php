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
use RTippin\MessengerBots\Bots\WeatherBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class WeatherBotTest extends MessengerBotsTestCase
{
    const DATA = [
        'location' => [
            'name' => 'Name',
            'region' => 'Region',
            'country' => 'Country',
        ],
        'current' => [
            'temp_f' => 99,
            'wind_mph' => 15,
            'wind_dir' => 'N',
            'humidity' => 69,
            'condition' => [
                'text' => 'partly cloudy',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([WeatherBot::class]);
        config()->set('messenger-bots.weather_api_key', 'WEATHER-KEY');
    }

    /** @test */
    public function it_is_registered()
    {
        $this->assertTrue(MessengerBots::isValidHandler(WeatherBot::class));
    }

    /** @test */
    public function it_gets_handler_dto()
    {
        $expected = [
            'alias' => 'weather',
            'description' => 'Get the current weather for the given location. [ !w {location} ]',
            'name' => 'Weather',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!w', '!weather'],
            'match' => 'starts:with:caseless',
        ];

        $this->assertSame($expected, WeatherBot::getDTO()->toArray());
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
            'handler' => 'weather',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function it_gets_response_and_stores_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!w Location')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            WeatherBot::API_ENDPOINT.'*' => Http::response(self::DATA),
        ]);
        $weather = MessengerBots::initializeHandler(WeatherBot::class)
            ->setDataForHandler($thread, $action, $message, '!w');

        $weather->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Currently in Name, Region, Country, it is 99 degrees fahrenheit and partly cloudy. Winds out of the N at 15mph. Humidity is 69%',
        ]);
        $this->assertFalse($weather->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_and_sends_error_message_when_http_fails()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!w Location')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            WeatherBot::API_ENDPOINT.'*' => Http::response([], 400),
        ]);
        $weather = MessengerBots::initializeHandler(WeatherBot::class)
            ->setDataForHandler($thread, $action, $message, '!w');

        $weather->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Please select a valid location, i.e. ( !w Orlando )',
        ]);
        $this->assertTrue($weather->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_and_sends_error_message_when_no_selection()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!w')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $weather = MessengerBots::initializeHandler(WeatherBot::class)
            ->setDataForHandler($thread, $action, $message, '!w');

        $weather->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Please select a valid location, i.e. ( !w Orlando )',
        ]);
        $this->assertTrue($weather->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!w Location')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        Http::fake([
            WeatherBot::API_ENDPOINT.'*' => Http::response(self::DATA),
        ]);

        MessengerBots::initializeHandler(WeatherBot::class)
            ->setDataForHandler($thread, $action, $message, '!w')
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }
}
