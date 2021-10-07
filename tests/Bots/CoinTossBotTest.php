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
use RTippin\MessengerBots\Bots\CoinTossBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class CoinTossBotTest extends MessengerBotsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([CoinTossBot::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'coin_toss',
            'description' => 'Toss a coin! Simple heads or tails. [ !toss {heads|tails} ]',
            'name' => 'Coin Toss',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!toss', '!headsOrTails', '!coinToss'],
            'match' => 'starts:with:caseless',
        ];

        $this->assertSame($expected, MessengerBots::getHandlerSettings(CoinTossBot::class));
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
            'handler' => 'coin_toss',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function it_uses_user_selection_and_stores_messages()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!toss heads')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $toss = MessengerBots::initializeHandler(CoinTossBot::class)
            ->setDataForHandler($thread, $action, $message, '!toss');

        $toss->handle();

        $this->assertDatabaseHas('messages', [
            'body' => ':cd: Heads or :dvd: Tails!',
        ]);
        $this->assertDatabaseCount('messages', 4);
        $this->assertFalse($toss->shouldReleaseCooldown());
    }

    /** @test */
    public function it_stores_messages_without_user_selection()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!toss')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $toss = MessengerBots::initializeHandler(CoinTossBot::class)
            ->setDataForHandler($thread, $action, $message, '!toss');

        $toss->handle();

        $this->assertDatabaseHas('messages', [
            'body' => ':cd: Heads or :dvd: Tails!',
        ]);
        $this->assertDatabaseCount('messages', 3);
        $this->assertFalse($toss->shouldReleaseCooldown());
    }

    /** @test */
    public function it_stores_invalid_selection_message_and_releases_cooldown()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!toss unknown')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $toss = MessengerBots::initializeHandler(CoinTossBot::class)
            ->setDataForHandler($thread, $action, $message, '!toss');

        $toss->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Please select a valid choice, i.e. ( !toss {heads|tails} )',
        ]);
        $this->assertDatabaseCount('messages', 2);
        $this->assertTrue($toss->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->body('!toss heads')->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        MessengerBots::initializeHandler(CoinTossBot::class)
            ->setDataForHandler($thread, $action, $message, '!toss')
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }
}
