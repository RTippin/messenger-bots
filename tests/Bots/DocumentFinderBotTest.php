<?php

namespace RTippin\MessengerBots\Tests\Bots;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ClientEvents\Typing;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\MessengerBots\Bots\DocumentFinderBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class DocumentFinderBotTest extends MessengerBotsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([DocumentFinderBot::class]);
    }

    /** @test */
    public function it_is_registered()
    {
        $this->assertTrue(MessengerBots::isValidHandler(DocumentFinderBot::class));
    }

    /** @test */
    public function it_gets_handler_dto()
    {
        $expected = [
            'alias' => 'document_finder',
            'description' => 'Search the group for uploaded documents. [ !document {search} ]',
            'name' => 'Document Finder',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!document', '!doc'],
            'match' => \RTippin\Messenger\MessengerBots::MATCH_STARTS_WITH_CASELESS,
        ];

        $this->assertSame($expected, DocumentFinderBot::getDTO()->toArray());
    }

    /** @test */
    public function it_sends_invalid_search_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!doc')->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        $finder = MessengerBots::initializeHandler(DocumentFinderBot::class)
            ->setDataForHandler($thread, $action, $message, '!doc');

        $finder->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Please select a valid search term, i.e. ( !document resume )',
        ]);
        $this->assertTrue($finder->shouldReleaseCooldown());
    }

    /** @test */
    public function it_sends_no_results_found_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!doc unknown')->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        $finder = MessengerBots::initializeHandler(DocumentFinderBot::class)
            ->setDataForHandler($thread, $action, $message, '!doc');

        $finder->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'I didn\'t find any document(s) matching ( unknown )',
        ]);
        $this->assertFalse($finder->shouldReleaseCooldown());
    }

    /** @test */
    public function it_finds_single_result()
    {
        $thread = $this->createGroupThread($this->tippin);
        $testPdf = Message::factory()->for($thread)->owner($this->tippin)->document()->body('testing.pdf')->create();
        $fooPdf = Message::factory()->for($thread)->owner($this->tippin)->document()->body('foo.pdf')->create();
        Message::factory()->for($thread)->owner($this->tippin)->body('!doc test')->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!doc test')->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        $finder = MessengerBots::initializeHandler(DocumentFinderBot::class)
            ->setDataForHandler($thread, $action, $message, '!doc');

        $finder->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'I found the following document(s) matching ( test ) :',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => ':floppy_disk: testing.pdf - '.$testPdf->getDocumentDownloadRoute(),
        ]);
        $this->assertDatabaseMissing('messages', [
            'body' => ':floppy_disk: foo.pdf - '.$fooPdf->getDocumentDownloadRoute(),
        ]);
        $this->assertFalse($finder->shouldReleaseCooldown());
    }

    /** @test */
    public function it_finds_multiple_results()
    {
        $thread = $this->createGroupThread($this->tippin);
        $testPdf = Message::factory()->for($thread)->owner($this->tippin)->document()->body('testing_foo.pdf')->create();
        $fooPdf = Message::factory()->for($thread)->owner($this->tippin)->document()->body('foo.pdf')->create();
        Message::factory()->for($thread)->owner($this->tippin)->body('!doc test')->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!doc foo')->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        $finder = MessengerBots::initializeHandler(DocumentFinderBot::class)
            ->setDataForHandler($thread, $action, $message, '!doc');

        $finder->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'I found the following document(s) matching ( foo ) :',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => ':floppy_disk: testing_foo.pdf - '.$testPdf->getDocumentDownloadRoute(),
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => ':floppy_disk: foo.pdf - '.$fooPdf->getDocumentDownloadRoute(),
        ]);
        $this->assertFalse($finder->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!doc unknown')->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        MessengerBots::initializeHandler(DocumentFinderBot::class)
            ->setDataForHandler($thread, $action, $message, '!doc')
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
            'handler' => 'document_finder',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'limit' => 5,
        ])
            ->assertSuccessful()
            ->assertJson([
                'payload' => [
                    'limit' => 5,
                ],
            ]);
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
        $resolve = DocumentFinderBot::testResolve([
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
     * @dataProvider failLimitValidation
     *
     * @param  $limit
     */
    public function it_fails_resolving_params($limit)
    {
        $resolve = DocumentFinderBot::testResolve([
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

    public static function failLimitValidation(): array
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
