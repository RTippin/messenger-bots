<?php

namespace RTippin\MessengerBots\Tests\Bots;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ClientEvents\Typing;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Message;
use RTippin\MessengerBots\Bots\InviteBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class InviteBotTest extends MessengerBotsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::registerHandlers([InviteBot::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'invitations',
            'description' => 'Generates a short-lived group invitation code and link.',
            'name' => 'Invite Generator',
            'unique' => true,
            'authorize' => false,
            'triggers' => ['!invite', '!inv'],
            'match' => 'exact:caseless',
        ];

        $this->assertSame($expected, MessengerBots::getHandlersDTO(InviteBot::class)->toArray());
    }

    /** @test */
    public function it_doesnt_make_invite_when_disabled()
    {
        Messenger::setThreadInvites(false);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        $invite = MessengerBots::initializeHandler(InviteBot::class)
            ->setDataForHandler($thread, $action, $message);

        $invite->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'Invites are currently disabled.',
            'reply_to_id' => $message->id,
        ]);
        $this->assertTrue($invite->shouldReleaseCooldown());
    }

    /** @test */
    public function it_doesnt_make_invite_when_group_has_max_allowed_active_invites()
    {
        Messenger::setThreadInvitesMaxCount(2);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        Invite::factory()->for($thread)->owner($this->tippin)->count(2)->create();
        $invite = MessengerBots::initializeHandler(InviteBot::class)
            ->setDataForHandler($thread, $action, $message);

        $invite->handle();

        $this->assertDatabaseHas('messages', [
            'body' => 'There are too many active invites.',
            'reply_to_id' => $message->id,
        ]);
        $this->assertTrue($invite->shouldReleaseCooldown());
    }

    /** @test */
    public function it_makes_invite_and_sends_message_without_link_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        $invite = MessengerBots::initializeHandler(InviteBot::class)
            ->setDataForHandler($thread, $action, $message);

        $invite->handle();

        $this->assertDatabaseCount('messages', 2);
        $this->assertDatabaseCount('thread_invites', 1);
        $this->assertFalse($invite->shouldReleaseCooldown());
    }

    /** @test */
    public function it_makes_invite_and_sends_message_with_link_message()
    {
        //end user must define route for the link message to be sent
        Route::get('join/{invite}', fn ($invite) => null)->name('messenger.invites.join');
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        $invite = MessengerBots::initializeHandler(InviteBot::class)
            ->setDataForHandler($thread, $action, $message);

        $invite->handle();

        $this->assertDatabaseCount('messages', 3);
        $this->assertDatabaseCount('thread_invites', 1);
        $this->assertFalse($invite->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        MessengerBots::initializeHandler(InviteBot::class)
            ->setDataForHandler($thread, $action, $message)
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
            'handler' => 'invitations',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'lifetime_minutes' => 30,
        ])
            ->assertSuccessful()
            ->assertJson([
                'payload' => [
                    'lifetime_minutes' => 30,
                ],
            ]);
    }

    /**
     * @test
     * @dataProvider passesLifetimeValidation
     *
     * @param $minutes
     */
    public function it_passes_validation_attaching_to_a_bot_handler($minutes)
    {
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'invitations',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'lifetime_minutes' => $minutes,
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     * @dataProvider failsLifetimeValidation
     *
     * @param $minutes
     */
    public function it_fails_validation_attaching_to_a_bot_handler($minutes)
    {
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'invitations',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'lifetime_minutes' => $minutes,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('lifetime_minutes');
    }

    public function passesLifetimeValidation(): array
    {
        return [
            'Nullable' => [null],
            'Min' => [5],
            'Max' => [60],
        ];
    }

    public function failsLifetimeValidation(): array
    {
        return [
            'Boolean' => [false],
            'Array' => [[1, 2]],
            'Under minimum' => [0],
            'Negative' => [-1],
            'Over maximum' => [61],
            'String' => ['Nope'],
        ];
    }
}
