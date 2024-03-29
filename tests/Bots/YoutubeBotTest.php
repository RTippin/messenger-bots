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
use RTippin\MessengerBots\Bots\YoutubeBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class YoutubeBotTest extends MessengerBotsTestCase
{
    const RESPONSE = [
        'items' => [
            ['id' => ['videoId' => 'dQw4w9WgXcQ']],
            ['id' => ['videoId' => 'b2F-DItXtZs']],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([YoutubeBot::class]);
    }

    /** @test */
    public function it_is_registered()
    {
        $this->assertTrue(MessengerBots::isValidHandler(YoutubeBot::class));
    }

    /** @test */
    public function it_gets_handler_dto()
    {
        $expected = [
            'alias' => 'youtube',
            'description' => 'Get the top video results for a youtube search. [ !youtube {search} ]',
            'name' => 'Youtube Videos Search',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!youtube', '!yt'],
            'match' => 'starts:with:caseless',
        ];

        $this->assertSame($expected, YoutubeBot::getDTO()->toArray());
    }

    /** @test */
    public function it_gets_response_and_stores_messages()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!youtube Rick-Roll')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            YoutubeBot::API_ENDPOINT.'*' => Http::response(self::RESPONSE),
        ]);
        $youtube = MessengerBots::initializeHandler(YoutubeBot::class)
            ->setDataForHandler($thread, $action, $message, '!youtube');

        $youtube->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'I found the following video(s) for ( Rick-Roll ) :',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'https://youtu.be/dQw4w9WgXcQ',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'https://youtu.be/b2F-DItXtZs',
        ]);
        $this->assertFalse($youtube->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_and_sends_error_message_when_http_fails()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!youtube Rick-Roll')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            YoutubeBot::API_ENDPOINT.'*' => Http::response([], 400),
        ]);
        $youtube = MessengerBots::initializeHandler(YoutubeBot::class)
            ->setDataForHandler($thread, $action, $message, '!youtube');

        $youtube->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Please select a valid search term, i.e. ( !youtube Stairway To Heaven )',
        ]);
        $this->assertTrue($youtube->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_and_sends_error_message_when_no_valid_search()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!youtube')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $youtube = MessengerBots::initializeHandler(YoutubeBot::class)
            ->setDataForHandler($thread, $action, $message, '!youtube');

        $youtube->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Please select a valid search term, i.e. ( !youtube Stairway To Heaven )',
        ]);
        $this->assertTrue($youtube->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!youtube Rick-Roll')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        Http::fake([
            YoutubeBot::API_ENDPOINT.'*' => Http::response(self::RESPONSE),
        ]);

        MessengerBots::initializeHandler(YoutubeBot::class)
            ->setDataForHandler($thread, $action, $message, '!youtube')
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
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
            'handler' => 'youtube',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'limit' => 2,
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     *
     * @dataProvider passesLimitValidation
     *
     * @param  $limit
     */
    public function it_passes_resolving_params($limit)
    {
        $resolve = YoutubeBot::testResolve([
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'limit' => $limit,
        ]);

        if (is_null($limit)) {
            $limit = 'null';
        }

        $this->assertSame('{"limit":'.$limit.'}', $resolve->payload);
    }

    /**
     * @test
     *
     * @dataProvider failsLimitValidation
     *
     * @param  $limit
     */
    public function it_fails_validation_attaching_to_a_bot_handler($limit)
    {
        $resolve = YoutubeBot::testResolve([
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'limit' => $limit,
        ]);

        $this->assertArrayHasKey('limit', $resolve);
    }

    public static function passesLimitValidation(): array
    {
        return [
            'Nullable' => [null],
            'Min' => [1],
            'Max' => [10],
        ];
    }

    public static function failsLimitValidation(): array
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
