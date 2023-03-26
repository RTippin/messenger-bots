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
use RTippin\MessengerBots\Bots\ReactionBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class ReactionBotTest extends MessengerBotsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([ReactionBot::class]);
    }

    /** @test */
    public function it_is_registered()
    {
        $this->assertTrue(MessengerBots::isValidHandler(ReactionBot::class));
    }

    /** @test */
    public function it_gets_handler_dto()
    {
        $expected = [
            'alias' => 'react',
            'description' => 'Adds a reaction to the message.',
            'name' => 'Reaction',
            'unique' => false,
            'authorize' => false,
            'triggers' => null,
            'match' => null,
        ];

        $this->assertSame($expected, ReactionBot::getDTO()->toArray());
    }

    /** @test */
    public function it_stores_reaction()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->payload(['reaction' => ':100:'])->create();
        $react = MessengerBots::initializeHandler(ReactionBot::class)
            ->setDataForHandler($thread, $action, $message);

        $react->handle();

        $this->assertDatabaseHas('message_reactions', [
            'reaction' => ':100:',
        ]);
        $this->assertFalse($react->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->payload(['reaction' => ':100:'])->create();
        Event::fake([
            ReactionAddedBroadcast::class,
            ReactionAddedEvent::class,
        ]);

        MessengerBots::initializeHandler(ReactionBot::class)
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
            'handler' => 'react',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['!100'],
            'reaction' => '💯',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function it_converts_emoji_to_shortcode_when_serialized()
    {
        $resolve = ReactionBot::testResolve([
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'match' => 'exact',
            'triggers' => ['!100'],
            'reaction' => '💩',
        ]);

        $this->assertSame('{"reaction":":poop:"}', $resolve->payload);
    }

    /**
     * @test
     *
     * @dataProvider passesEmojiValidation
     *
     * @param $reaction
     */
    public function it_passes_resolving_params($reaction)
    {
        $resolve = ReactionBot::testResolve([
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'match' => 'exact',
            'triggers' => ['!100'],
            'reaction' => $reaction,
        ]);

        $this->assertInstanceOf(ResolvedBotHandlerDTO::class, $resolve);
    }

    /**
     * @test
     *
     * @dataProvider failsEmojiValidation
     *
     * @param $reaction
     */
    public function it_fails_resolving_params($reaction)
    {
        $resolve = ReactionBot::testResolve([
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'match' => 'exact',
            'triggers' => ['!100'],
            'reaction' => $reaction,
        ]);

        $this->assertArrayHasKey('reaction', $resolve);
    }

    public static function passesEmojiValidation(): array
    {
        return [
            'Basic emoji shortcode' => [':poop:'],
            'Basic emoji utf8' => ['💩'],
            'Basic unicode emoji (:x:)' => ["\xE2\x9D\x8C"],
            'Basic ascii emoji' => [':)'],
            'Emoji found within string' => ['I tried to break :poop:'],
            'Emoji found within string after failed emoji' => ['I tried to break :unknown: :poop:'],
            'Multiple emojis it will pick first' => ['💩 :poop: 😁'],
        ];
    }

    public static function failsEmojiValidation(): array
    {
        return [
            'Unknown emoji shortcode' => [':unknown:'],
            'String with no emojis' => ['I have no emojis'],
            'Invalid if shortcode spaced' => [': poop :'],
            'Cannot be empty' => [''],
            'Cannot be null' => [null],
            'Cannot be array' => [[0, 1]],
            'Cannot be integer' => [1],
            'Cannot be boolean' => [false],
        ];
    }
}
