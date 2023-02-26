<?php

namespace RTippin\MessengerBots\Tests\Bots;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ReactionAddedBroadcast;
use RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO;
use RTippin\Messenger\Events\ReactionAddedEvent;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\MessengerBots\Bots\ReactionBombBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class ReactionBombBotTest extends MessengerBotsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([ReactionBombBot::class]);
    }

    /** @test */
    public function it_is_registered()
    {
        $this->assertTrue(MessengerBots::isValidHandler(ReactionBombBot::class));
    }

    /** @test */
    public function it_gets_handler_dto()
    {
        $expected = [
            'alias' => 'react_bomb',
            'description' => 'All bots in the thread will add the specified reaction(s) to the message.',
            'name' => 'Reaction Bomb',
            'unique' => false,
            'authorize' => false,
            'triggers' => null,
            'match' => null,
        ];

        $this->assertSame($expected, ReactionBombBot::getDTO()->toArray());
    }

    /** @test */
    public function it_stores_reactions()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->payload(['reactions' => [':100:', ':poop:']])->create();
        $react = MessengerBots::initializeHandler(ReactionBombBot::class)
            ->setDataForHandler($thread, $action, $message);

        $react->handle();

        $this->assertDatabaseHas('message_reactions', [
            'reaction' => ':100:',
        ]);
        $this->assertDatabaseHas('message_reactions', [
            'reaction' => ':poop:',
        ]);
        $this->assertFalse($react->shouldReleaseCooldown());
    }

    /** @test */
    public function it_stores_reactions_using_all_enabled_thread_bots()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $bot1 = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $bot2 = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $bot3 = Bot::factory()->for($thread)->owner($this->tippin)->disabled()->create();
        $action = BotAction::factory()
            ->for($bot1)
            ->owner($this->tippin)
            ->payload(['reactions' => [':100:']])
            ->create();
        $react = MessengerBots::initializeHandler(ReactionBombBot::class)
            ->setDataForHandler($thread, $action, $message);

        $react->handle();

        $this->assertDatabaseHas('message_reactions', [
            'reaction' => ':100:',
            'owner_id' => $bot1->getKey(),
            'owner_type' => 'bots',
        ]);
        $this->assertDatabaseHas('message_reactions', [
            'reaction' => ':100:',
            'owner_id' => $bot2->getKey(),
            'owner_type' => 'bots',
        ]);
        $this->assertDatabaseMissing('message_reactions', [
            'reaction' => ':100:',
            'owner_id' => $bot3->getKey(),
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
        )->owner($this->tippin)->payload(['reactions' => [':100:', ':poop:']])->create();
        Event::fake([
            ReactionAddedBroadcast::class,
            ReactionAddedEvent::class,
        ]);

        MessengerBots::initializeHandler(ReactionBombBot::class)
            ->setDataForHandler($thread, $action, $message)
            ->handle();

        Event::assertDispatched(ReactionAddedBroadcast::class);
        Event::assertDispatched(ReactionAddedEvent::class);
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
            'handler' => 'react_bomb',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['testing'],
            'reactions' => ['💯', '💩'],
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function it_converts_emoji_to_shortcode_when_serialized()
    {
        $resolve = ReactionBombBot::testResolve([
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'match' => 'exact',
            'triggers' => ['testing'],
            'reactions' => ['💯', '💩'],
        ]);

        $this->assertSame('{"reactions":[":100:",":poop:"]}', $resolve->payload);
    }

    /**
     * @test
     * @dataProvider passesEmojiValidation
     *
     * @param $reactions
     */
    public function it_passes_resolving_params($reactions)
    {
        $resolve = ReactionBombBot::testResolve([
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'match' => 'exact',
            'triggers' => ['!100'],
            'reactions' => $reactions,
        ]);

        $this->assertInstanceOf(ResolvedBotHandlerDTO::class, $resolve);
    }

    /**
     * @test
     * @dataProvider failsEmojiValidation
     *
     * @param $reactions
     */
    public function it_fails_resolving_params($reactions, $limitTest = false)
    {
        $resolve = ReactionBombBot::testResolve([
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'match' => 'exact',
            'triggers' => ['!100'],
            'reactions' => $reactions,
        ]);

        $this->assertArrayHasKey($limitTest ? 'reactions' : 'reactions.0', $resolve);
    }

    public static function passesEmojiValidation(): array
    {
        return [
            'Basic emoji shortcode' => [[':poop:']],
            'Basic emoji utf8' => [['💩']],
            'Basic unicode emoji (:x:)' => [["\xE2\x9D\x8C"]],
            'Basic ascii emoji' => [[':)']],
            'Emoji found within string' => [['I tried to break :poop:']],
            'Emoji found within string after failed emoji' => [['I tried to break :unknown: :poop:']],
            'Multiple emojis within one entry will use first found' => [['💩 :poop: 😁']],
            'Multiple different emojis' => [[':one:', ':two:']],
            'Max of 10 emojis' => [
                [
                    ':one:',
                    ':two:',
                    ':three:',
                    ':four:',
                    ':five:',
                    ':six:',
                    ':seven:',
                    ':eight:',
                    ':nine:',
                    ':poop:',
                ],
            ],
        ];
    }

    public static function failsEmojiValidation(): array
    {
        return [
            'Unknown emoji shortcode' => [[':unknown:']],
            'String with no emojis' => [['I have no emojis']],
            'Invalid if shortcode spaced' => [[': poop :']],
            'Cannot be empty' => [['']],
            'Cannot be null' => [[null]],
            'Cannot be array' => [[[0, 1]]],
            'Cannot be integer' => [[1]],
            'Cannot be boolean' => [[false]],
            'Cannot be more than 10 emojis' => [
                [
                    ':one:',
                    ':two:',
                    ':three:',
                    ':four:',
                    ':five:',
                    ':six:',
                    ':seven:',
                    ':eight:',
                    ':nine:',
                    ':poop:',
                    ':100:',
                ], true,
            ],
        ];
    }
}
