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
use RTippin\MessengerBots\Bots\GiphyBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class GiphyBotTest extends MessengerBotsTestCase
{
    const DATA = [
        'data' => [
            'image_url' => 'https://media.giphy.com/media/YQitE4YNQNahy/giphy.gif',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([GiphyBot::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'giphy',
            'description' => 'Get a random gif from giphy, with an optional tag. [ !gif {tag?} ]',
            'name' => 'Giphy',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!gif', '!giphy'],
            'match' => 'starts:with:caseless',
        ];

        $this->assertSame($expected, MessengerBots::getHandlerSettings(GiphyBot::class));
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
            'handler' => 'giphy',
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
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            GiphyBot::API_ENDPOINT.'*' => Http::response(self::DATA),
        ]);
        $giphy = MessengerBots::initializeHandler(GiphyBot::class)
            ->setDataForMessage($thread, $action, $message);

        $giphy->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'https://media.giphy.com/media/YQitE4YNQNahy/giphy.gif',
            'owner_type' => 'bots',
        ]);
        $this->assertFalse($giphy->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_when_http_fails()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            GiphyBot::API_ENDPOINT.'*' => Http::response([], 400),
        ]);
        $giphy = MessengerBots::initializeHandler(GiphyBot::class)
            ->setDataForMessage($thread, $action, $message);

        $giphy->handle();

        $this->assertTrue($giphy->shouldReleaseCooldown());
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
            GiphyBot::API_ENDPOINT.'*' => Http::response(self::DATA),
        ]);

        MessengerBots::initializeHandler(GiphyBot::class)
            ->setDataForMessage($thread, $action, $message)
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }
}
