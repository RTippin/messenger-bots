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
use RTippin\MessengerBots\Bots\ReplyBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class ReplyBotTest extends MessengerBotsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([ReplyBot::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'reply',
            'description' => 'Reply with the given response(s).',
            'name' => 'Reply',
            'unique' => false,
            'authorize' => false,
            'triggers' => null,
            'match' => null,
        ];

        $this->assertSame($expected, MessengerBots::getHandlerSettings(ReplyBot::class));
    }

    /** @test */
    public function it_stores_single_message()
    {
        $payload = [
            'quote_original' => false,
            'replies' => ['First'],
        ];
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->payload($payload)->create();

        MessengerBots::initializeHandler(ReplyBot::class)
            ->setDataForMessage($thread, $action, $message)
            ->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'First',
            'reply_to_id' => null,
        ]);
    }

    /** @test */
    public function it_stores_multiple_messages_making_first_reply_to_trigger_message()
    {
        $payload = [
            'quote_original' => true,
            'replies' => ['First', 'Second', 'Third'],
        ];
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->payload($payload)->create();

        MessengerBots::initializeHandler(ReplyBot::class)
            ->setDataForMessage($thread, $action, $message)
            ->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'First',
            'reply_to_id' => $message->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'Second',
            'reply_to_id' => null,
        ]);
        $this->assertDatabaseHas('messages', [
            'body' => 'Third',
            'reply_to_id' => null,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $payload = [
            'quote_original' => true,
            'replies' => ['First'],
        ];
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->payload($payload)->create();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        MessengerBots::initializeHandler(ReplyBot::class)
            ->setDataForMessage($thread, $action, $message)
            ->handle();

        Event::assertDispatched(NewMessageBroadcast::class);
        Event::assertDispatched(NewMessageEvent::class);
        Event::assertDispatched(Typing::class);
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
            'handler' => 'reply',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['!reply'],
            'quote_original' => false,
            'replies' => ['One', 'Two 💯'],
        ])
            ->assertSuccessful()
            ->assertJson([
                'payload' => [
                    'quote_original' => false,
                    'replies' => ['One', 'Two :100:'],
                ],
            ]);
    }

    /**
     * @test
     * @dataProvider passesValidation
     * @param $quote
     * @param $replies
     */
    public function it_passes_validation_attaching_to_a_bot_handler($quote, $replies)
    {
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'reply',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['!reply'],
            'quote_original' => $quote,
            'replies' => $replies,
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     * @dataProvider failsValidation
     * @param $quote
     * @param $replies
     */
    public function it_fails_validation_attaching_to_a_bot_handler($quote, $replies)
    {
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'reply',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['!reply'],
            'quote_original' => $quote,
            'replies' => $replies,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quote_original', 'replies']);
    }

    public function passesValidation(): array
    {
        return [
            'True and one reply' => [true, ['1']],
            'False and two replies' => [false, ['1', '2']],
            'True and three replies' => [true, ['1', '2', '3']],
            'False and four replies' => [false, ['1', '2', '3', '4']],
            'True and five replies' => [false, ['1', '2', '3', '4', '5']],
        ];
    }

    public function failsValidation(): array
    {
        return [
            'Required' => [null, null],
            'Int and empty array' => [5, []],
            'Array and empty array' => [[1, 2], 'string'],
            'String and more than five' => [5, ['1', '2', '3', '4', '5', '6']],
        ];
    }
}
