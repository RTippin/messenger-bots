<?php

namespace RTippin\MessengerBots\Tests\Bots;

use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ClientEvents\Typing;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\MessengerBots\Bots\KanyeBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class KanyeBotTest extends MessengerBotsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::setHandlers([KanyeBot::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'kanye',
            'description' => 'Get a random Kanye West quote.',
            'name' => 'Kanye West',
            'unique' => true,
            'authorize' => false,
            'triggers' => null,
            'match' => null,
        ];

        $this->assertSame($expected, MessengerBots::getHandlerSettings(KanyeBot::class));
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
            'handler' => 'kanye',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['!kanye'],
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function it_gets_response_and_stores_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            'https://api.kanye.rest/' => Http::response(['quote' => 'Kanye da bomb.']),
        ]);
        $kanye = MessengerBots::initializeHandler(KanyeBot::class)
            ->setDataForMessage($thread, $action, $message, null, null);

        $kanye->handle();

        $this->assertDatabaseHas('messages', [
            'body' => ':bearded_person_tone5: "Kanye da bomb."',
        ]);
        $this->assertFalse($kanye->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_when_http_fails()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            'https://api.kanye.rest/' => Http::response([], 400),
        ]);
        $kanye = MessengerBots::initializeHandler(KanyeBot::class)
            ->setDataForMessage($thread, $action, $message, null, null);

        $kanye->handle();

        $this->assertTrue($kanye->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        Http::fake([
            'https://api.kanye.rest/' => Http::response(['quote' => 'Kanye da bomb.']),
        ]);

        MessengerBots::initializeHandler(KanyeBot::class)
            ->setDataForMessage($thread, $action, $message, null, null)
            ->handle();
    }
}
