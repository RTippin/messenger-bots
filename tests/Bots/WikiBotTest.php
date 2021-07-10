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
use RTippin\MessengerBots\Bots\WikiBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class WikiBotTest extends MessengerBotsTestCase
{
    const DATA = [
        'PHP',
        ['PHP', 'PhpStorm'],
        ['', ''],
        ['https://en.wikipedia.org/wiki/PHP', 'https://en.wikipedia.org/wiki/PhpStorm'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::setHandlers([WikiBot::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'wiki',
            'description' => 'Get the top results for a wikipedia article search. [ !wiki {search term} ]',
            'name' => 'Wikipedia Search',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!wiki'],
            'match' => 'starts:with:caseless',
        ];

        $this->assertSame($expected, MessengerBots::getHandlerSettings(WikiBot::class));
    }

    /** @test */
    public function it_gets_response_and_stores_messages()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!wiki PHP']);
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            WikiBot::API_ENDPOINT.'&limit=3&search=PHP' => Http::response(self::DATA),
        ]);
        $wiki = MessengerBots::initializeHandler(WikiBot::class)
            ->setDataForMessage($thread, $action, $message, '!wiki', null);

        $wiki->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'I found the following article(s) for ( PHP ) :',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'PHP - https://en.wikipedia.org/wiki/PHP',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'PhpStorm - https://en.wikipedia.org/wiki/PhpStorm',
        ]);
        $this->assertFalse($wiki->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_and_sends_error_message_when_http_fails()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!wiki PHP']);
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            WikiBot::API_ENDPOINT.'&limit=3&search=PHP' => Http::response([], 400),
        ]);
        $wiki = MessengerBots::initializeHandler(WikiBot::class)
            ->setDataForMessage($thread, $action, $message, '!wiki', null);

        $wiki->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Please select a valid search term, i.e. ( !wiki Computers )',
        ]);
        $this->assertTrue($wiki->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_and_sends_error_message_when_no_valid_search()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!wiki']);
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $wiki = MessengerBots::initializeHandler(WikiBot::class)
            ->setDataForMessage($thread, $action, $message, '!wiki', null);

        $wiki->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Please select a valid search term, i.e. ( !wiki Computers )',
        ]);
        $this->assertTrue($wiki->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!wiki PHP']);
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        Http::fake([
            WikiBot::API_ENDPOINT.'&limit=3&search=PHP' => Http::response(self::DATA),
        ]);

        MessengerBots::initializeHandler(WikiBot::class)
            ->setDataForMessage($thread, $action, $message, '!wiki', null)
            ->handle();
    }

    /** @test */
    public function it_serializes_payload_when_attaching_to_a_bot_handler()
    {
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'wiki',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'limit' => 2,
        ])
            ->assertSuccessful()
            ->assertJson([
                'payload' => [
                    'limit' => 2,
                ],
            ]);
    }

    /**
     * @test
     * @dataProvider passesLimitValidation
     * @param $limit
     */
    public function it_passes_validation_attaching_to_a_bot_handler($limit)
    {
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'wiki',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'limit' => $limit,
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     * @dataProvider failsLimitValidation
     * @param $limit
     */
    public function it_fails_validation_attaching_to_a_bot_handler($limit)
    {
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'wiki',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'limit' => $limit,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('limit');
    }

    public function passesLimitValidation(): array
    {
        return [
            'Nullable' => [null],
            'Min' => [1],
            'Max' => [10],
        ];
    }

    public function failsLimitValidation(): array
    {
        return [
            'Boolean' => [false],
            'Array' => [[1, 2]],
            'Under minimum' => [0],
            'Negative' => [-1],
            'Over maximum' => [11],
            'String' => ['Nope'],
        ];
    }
}