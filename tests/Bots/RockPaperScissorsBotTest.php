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
use RTippin\MessengerBots\Bots\RockPaperScissorsBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class RockPaperScissorsBotTest extends MessengerBotsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([RockPaperScissorsBot::class]);
    }

    /** @test */
    public function it_is_registered()
    {
        $this->assertTrue(MessengerBots::isValidHandler(RockPaperScissorsBot::class));
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'rock_paper_scissors',
            'description' => 'Play a game of rock, paper, scissors! [ !rps {rock|paper|scissors} ]',
            'name' => 'Rock Paper Scissors',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!rps'],
            'match' => 'starts:with:caseless',
        ];

        $this->assertSame($expected, MessengerBots::getHandlers(RockPaperScissorsBot::class)->toArray());
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
            'handler' => 'rock_paper_scissors',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function it_plays_game_and_stores_messages()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!rps rock')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $game = MessengerBots::initializeHandler(RockPaperScissorsBot::class)
            ->setDataForHandler($thread, $action, $message, '!rps');

        $game->handle();

        $this->assertDatabaseHas('messages', [
            'body' => ':mountain: Rock! :page_facing_up: Paper! :scissors: Scissors!',
        ]);
        $this->assertDatabaseCount('messages', 4);
        $this->assertFalse($game->shouldReleaseCooldown());
    }

    /** @test */
    public function it_plays_game_without_user_selection()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!rps')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $game = MessengerBots::initializeHandler(RockPaperScissorsBot::class)
            ->setDataForHandler($thread, $action, $message, '!rps');

        $game->handle();

        $this->assertDatabaseHas('messages', [
            'body' => ':mountain: Rock! :page_facing_up: Paper! :scissors: Scissors!',
        ]);
        $this->assertDatabaseCount('messages', 3);
        $this->assertFalse($game->shouldReleaseCooldown());
    }

    /** @test */
    public function it_stores_invalid_selection_message_and_releases_cooldown()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!rps unknown')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $game = MessengerBots::initializeHandler(RockPaperScissorsBot::class)
            ->setDataForHandler($thread, $action, $message, '!rps');

        $game->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Please select a valid choice, i.e. ( !rps rock|paper|scissors )',
        ]);
        $this->assertDatabaseCount('messages', 2);
        $this->assertTrue($game->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!rps rock')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        MessengerBots::initializeHandler(RockPaperScissorsBot::class)
            ->setDataForHandler($thread, $action, $message, '!rps')
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }
}
