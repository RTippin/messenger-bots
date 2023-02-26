<?php

namespace RTippin\MessengerBots\Tests\Bots;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\MessageArchivedBroadcast;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO;
use RTippin\Messenger\Events\MessageArchivedEvent;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\MessengerBots\Bots\NukeBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class NukeBotTest extends MessengerBotsTestCase
{
    const PARAMS = [
        'handler' => 'nuke',
        'cooldown' => 0,
        'admin_only' => true,
        'enabled' => true,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([NukeBot::class]);
    }

    /** @test */
    public function it_is_registered()
    {
        $this->assertTrue(MessengerBots::isValidHandler(NukeBot::class));
    }

    /** @test */
    public function it_gets_handler_dto()
    {
        $expected = [
            'alias' => 'nuke',
            'description' => 'Delete between 5 and 100 past messages, default of 15. [ !nuke {count} ]',
            'name' => 'Nuke Messages',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!nuke'],
            'match' => 'starts:with:caseless',
        ];

        $this->assertSame($expected, NukeBot::getDTO()->toArray());
    }

    /** @test */
    public function it_passes_resolving_params()
    {
        $this->assertInstanceOf(ResolvedBotHandlerDTO::class, NukeBot::testResolve(self::PARAMS));
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
    public function it_nukes_without_count_and_stores_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        Message::factory()->for($thread)->owner($this->tippin)->count(5)->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!nuke')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $nuke = MessengerBots::initializeHandler(NukeBot::class)
            ->setDataForHandler($thread, $action, $message, '!nuke');

        $nuke->handle();

        $this->assertDatabaseHas('messages', [
            'body' => ':bomb:',
        ]);
        $this->assertSame(1, $thread->messages()->count());
        $this->assertFalse($nuke->shouldReleaseCooldown());
    }

    /** @test */
    public function it_nukes_specified_count()
    {
        $thread = $this->createGroupThread($this->tippin);
        Message::factory()->for($thread)->owner($this->tippin)->count(10)->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!nuke 5')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $nuke = MessengerBots::initializeHandler(NukeBot::class)
            ->setDataForHandler($thread, $action, $message, '!nuke');

        $nuke->handle();

        $this->assertSame(7, $thread->messages()->count());
    }

    /** @test */
    public function it_nukes_without_removing_system_messages()
    {
        $thread = $this->createGroupThread($this->tippin);
        Message::factory()->for($thread)->owner($this->tippin)->count(5)->create();
        Message::factory()->for($thread)->owner($this->tippin)->system()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!nuke')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $nuke = MessengerBots::initializeHandler(NukeBot::class)
            ->setDataForHandler($thread, $action, $message, '!nuke');

        $nuke->handle();

        $this->assertSame(2, $thread->messages()->count());
        $this->assertFalse($nuke->shouldReleaseCooldown());
    }

    /**
     * @test
     * @dataProvider failsNukeCount
     *
     * @param $count
     */
    public function it_stores_invalid_count_message_and_releases_cooldown($count)
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body("!nuke $count")->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $nuke = MessengerBots::initializeHandler(NukeBot::class)
            ->setDataForHandler($thread, $action, $message, '!nuke');

        $nuke->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Please select a valid message count between 5 and 100, i.e. ( !nuke 25 )',
        ]);
        $this->assertTrue($nuke->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!nuke')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            MessageArchivedBroadcast::class,
            MessageArchivedEvent::class,
        ]);

        MessengerBots::initializeHandler(NukeBot::class)
            ->setDataForHandler($thread, $action, $message, '!nuke')
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(MessageArchivedBroadcast::class);
        Event::assertDispatched(MessageArchivedEvent::class);
    }

    public static function failsNukeCount(): array
    {
        return [
            'Invalid count' => ['unknown'],
            'Cannot be negative' => [-1],
            'Cannot be less than 5' => [4],
            'Cannot be more than 100' => [101],
        ];
    }
}
