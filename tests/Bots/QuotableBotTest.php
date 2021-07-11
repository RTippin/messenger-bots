<?php

namespace RTippin\MessengerBots\Tests\Bots;

use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ClientEvents\Typing;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\MessengerBots\Bots\QuotableBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class QuotableBotTest extends MessengerBotsTestCase
{
    const DATA = [
        'data' => [
            [
                'quoteText' => 'Do better!',
                'quoteAuthor' => 'Some Guy',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::setHandlers([QuotableBot::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'quotable',
            'description' => 'Get a random quote.',
            'name' => 'Quotable Quotes',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!quote', '!inspire', '!quotable'],
            'match' => 'exact:caseless',
        ];

        $this->assertSame($expected, MessengerBots::getHandlerSettings(QuotableBot::class));
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
            'handler' => 'quotable',
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
            QuotableBot::API_ENDPOINT => Http::response(self::DATA),
        ]);
        $quote = MessengerBots::initializeHandler(QuotableBot::class)
            ->setDataForMessage($thread, $action, $message, null, null);

        $quote->handle();

        $this->assertDatabaseHas('messages', [
            'body' => ':speech_left: "Do better!" - Some Guy',
        ]);
        $this->assertFalse($quote->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_when_http_fails()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            QuotableBot::API_ENDPOINT => Http::response([], 400),
        ]);
        $quote = MessengerBots::initializeHandler(QuotableBot::class)
            ->setDataForMessage($thread, $action, $message, null, null);

        $quote->handle();

        $this->assertTrue($quote->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        Http::fake([
            QuotableBot::API_ENDPOINT => Http::response(self::DATA),
        ]);

        MessengerBots::initializeHandler(QuotableBot::class)
            ->setDataForMessage($thread, $action, $message, null, null)
            ->handle();
    }
}
