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
use RTippin\MessengerBots\Bots\RollBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class RollBotTest extends MessengerBotsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([RollBot::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'roll',
            'description' => 'Rolls a random number between 0 and 100. You may also specify the number range to roll between. [ !roll {start} {end} ]',
            'name' => 'Roll Numbers',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!r', '!roll'],
            'match' => 'starts:with:caseless',
        ];

        $this->assertSame($expected, MessengerBots::getHandlersDTO(RollBot::class)->toArray());
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
            'handler' => 'roll',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function it_rolls_without_selection_and_stores_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!roll')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $roll = MessengerBots::initializeHandler(RollBot::class)
            ->setDataForHandler($thread, $action, $message, '!roll');

        $roll->handle();

        $this->assertDatabaseCount('messages', 2);
        $this->assertFalse($roll->shouldReleaseCooldown());
    }

    /** @test */
    public function it_stores_invalid_selection_message_and_releases_cooldown()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!roll 1 unknown')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $roll = MessengerBots::initializeHandler(RollBot::class)
            ->setDataForHandler($thread, $action, $message, '!roll');

        $roll->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Please select a valid number range, i.e. ( !r 1 50 )',
        ]);
        $this->assertTrue($roll->shouldReleaseCooldown());
    }

    /** @test */
    public function it_rolls_between_desired_numbers_and_stores_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!roll 1 1')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $roll = MessengerBots::initializeHandler(RollBot::class)
            ->setDataForHandler($thread, $action, $message, '!roll');

        $roll->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Rolling (1 - 1), Got: 1',
        ]);
        $this->assertFalse($roll->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!roll')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        MessengerBots::initializeHandler(RollBot::class)
            ->setDataForHandler($thread, $action, $message, '!roll')
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }
}
