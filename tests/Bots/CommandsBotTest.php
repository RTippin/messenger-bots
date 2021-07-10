<?php

namespace RTippin\MessengerBots\Tests\Bots;

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

        MessengerBots::setHandlers([CommandsBot::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'commands',
            'description' => 'List all triggers the current bot has across its actions.',
            'name' => 'List Commands / Triggers',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!commands', '!c'],
            'match' => 'exact:caseless',
        ];

        $this->assertSame($expected, MessengerBots::getHandlerSettings(CommandsBot::class));
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
            ->setDataForMessage($thread, $action, $message, null, null)
            ->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Richard Tippin, I can respond to the following commands:',
            'owner_type' => 'bots',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'List Commands / Triggers - ( !commands|!c )',
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
        MessengerBots::setHandlers($handlers);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(CommandsBot::class)->triggers('!commands|!c')->create();

        foreach ($handlers as $handler) {
            BotAction::factory()->for($bot)->owner($this->tippin)->handler($handler)->triggers('!trigger')->create();
        }

        MessengerBots::initializeHandler(CommandsBot::class)
            ->setDataForMessage($thread, $action, $message, null, null)
            ->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Richard Tippin, I can respond to the following commands:',
            'owner_type' => 'bots',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'Chuck Norris - ( !trigger ), Dad Joke - ( !trigger ), Insult - ( !trigger ), Jokester - ( !trigger ), Kanye West - ( !trigger )',
            'owner_type' => 'bots',
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'Knock Knock - ( !trigger ), List Commands / Triggers - ( !commands|!c )',
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

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        MessengerBots::initializeHandler(CommandsBot::class)
            ->setDataForMessage($thread, $action, $message, null, null)
            ->handle();
    }
}