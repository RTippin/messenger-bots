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
use RTippin\MessengerBots\Bots\ChuckNorrisBot;
use RTippin\MessengerBots\Bots\CommandsBot;
use RTippin\MessengerBots\Bots\DadJokeBot;
use RTippin\MessengerBots\Bots\InsultBot;
use RTippin\MessengerBots\Bots\JokeBot;
use RTippin\MessengerBots\Bots\KanyeBot;
use RTippin\MessengerBots\Bots\KnockBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class CommandsBotTest extends MessengerBotsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([CommandsBot::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'commands',
            'description' => 'List all actions and triggers that all bots in the group have attached.',
            'name' => 'List Commands',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!commands', '!c'],
            'match' => \RTippin\Messenger\MessengerBots::MATCH_EXACT_CASELESS,
        ];

        $this->assertSame($expected, MessengerBots::getHandlers(CommandsBot::class)->toArray());
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
            'handler' => 'commands',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function it_stores_messages()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->handler(CommandsBot::class)->triggers('!commands|!c')->create();

        MessengerBots::initializeHandler(CommandsBot::class)
            ->setDataForHandler($thread, $action, $message)
            ->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Richard Tippin, we can respond to the following commands:',
            'owner_type' => 'bots',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'List Commands - [ !commands | !c ]',
            'owner_type' => 'bots',
        ]);
    }

    /** @test */
    public function it_gets_actions_across_all_bots_in_a_thread()
    {
        MessengerBots::registerHandlers([ChuckNorrisBot::class]);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->handler(ChuckNorrisBot::class)->triggers('!chuck')->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->handler(CommandsBot::class)->triggers('!commands|!c')->create();

        MessengerBots::initializeHandler(CommandsBot::class)
            ->setDataForHandler($thread, $action, $message)
            ->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Richard Tippin, we can respond to the following commands:',
            'owner_type' => 'bots',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'Chuck Norris - [ !chuck ], List Commands - [ !commands | !c ]',
            'owner_type' => 'bots',
        ]);
    }

    /** @test */
    public function it_stores_multiple_messages_if_more_than_5_handlers()
    {
        $handlers = [
            ChuckNorrisBot::class,
            DadJokeBot::class,
            InsultBot::class,
            JokeBot::class,
            KanyeBot::class,
            KnockBot::class,
        ];
        MessengerBots::registerHandlers($handlers);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(CommandsBot::class)->triggers('!commands|!c')->create();

        foreach ($handlers as $handler) {
            BotAction::factory()->for($bot)->owner($this->tippin)->handler($handler)->triggers('!trigger')->create();
        }

        MessengerBots::initializeHandler(CommandsBot::class)
            ->setDataForHandler($thread, $action, $message)
            ->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Richard Tippin, we can respond to the following commands:',
            'owner_type' => 'bots',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'Chuck Norris - [ !trigger ], Dad Joke - [ !trigger ], Insult - [ !trigger ], Jokester - [ !trigger ], Kanye West - [ !trigger ]',
            'owner_type' => 'bots',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'Knock Knock - [ !trigger ], List Commands - [ !commands | !c ]',
            'owner_type' => 'bots',
        ]);
    }

    /** @test */
    public function it_ignores_admin_only_actions_if_not_admin()
    {
        $handlers = [
            ChuckNorrisBot::class,
            DadJokeBot::class,
            InsultBot::class,
        ];
        MessengerBots::registerHandlers($handlers);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(CommandsBot::class)->triggers('!commands|!c')->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(ChuckNorrisBot::class)->triggers('!trigger')->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(DadJokeBot::class)->triggers('!trigger')->admin()->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(InsultBot::class)->triggers('!trigger')->create();

        MessengerBots::initializeHandler(CommandsBot::class)
            ->setDataForHandler($thread, $action, $message)
            ->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Richard Tippin, we can respond to the following commands:',
            'owner_type' => 'bots',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'Chuck Norris - [ !trigger ], Insult - [ !trigger ], List Commands - [ !commands | !c ]',
            'owner_type' => 'bots',
        ]);
    }

    /** @test */
    public function it_includes_admin_only_actions_if_admin()
    {
        $handlers = [
            ChuckNorrisBot::class,
            DadJokeBot::class,
            InsultBot::class,
        ];
        MessengerBots::registerHandlers($handlers);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(CommandsBot::class)->triggers('!commands|!c')->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(ChuckNorrisBot::class)->triggers('!trigger')->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(DadJokeBot::class)->triggers('!trigger')->admin()->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(InsultBot::class)->triggers('!trigger')->create();

        MessengerBots::initializeHandler(CommandsBot::class)
            ->setDataForHandler($thread, $action, $message, null, true)
            ->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Richard Tippin, we can respond to the following commands:',
            'owner_type' => 'bots',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'Chuck Norris - [ !trigger ], Dad Joke - [ !trigger ], Insult - [ !trigger ], List Commands - [ !commands | !c ]',
            'owner_type' => 'bots',
        ]);
    }

    /** @test */
    public function it_ignores_disabled_actions()
    {
        $handlers = [
            ChuckNorrisBot::class,
            DadJokeBot::class,
            InsultBot::class,
        ];
        MessengerBots::registerHandlers($handlers);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(CommandsBot::class)->triggers('!commands|!c')->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(ChuckNorrisBot::class)->triggers('!trigger')->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(DadJokeBot::class)->triggers('!trigger')->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(InsultBot::class)->triggers('!trigger')->disabled()->create();

        MessengerBots::initializeHandler(CommandsBot::class)
            ->setDataForHandler($thread, $action, $message)
            ->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Richard Tippin, we can respond to the following commands:',
            'owner_type' => 'bots',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'Chuck Norris - [ !trigger ], Dad Joke - [ !trigger ], List Commands - [ !commands | !c ]',
            'owner_type' => 'bots',
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->handler(CommandsBot::class)->triggers('!commands|!c')->create();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        MessengerBots::initializeHandler(CommandsBot::class)
            ->setDataForHandler($thread, $action, $message)
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
    }
}
