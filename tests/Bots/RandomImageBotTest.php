<?php

namespace RTippin\MessengerBots\Tests\Bots;

use Exception;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Messages\StoreImageMessage;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\MessengerBots\Bots\RandomImageBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class RandomImageBotTest extends MessengerBotsTestCase
{
    const DATA = ['quote' => 'Kanye da bomb.'];

    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::setHandlers([RandomImageBot::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'random_image',
            'description' => 'Get a random image.',
            'name' => 'Random Image',
            'unique' => true,
            'authorize' => false,
            'triggers' => null,
            'match' => null,
        ];

        $this->assertSame($expected, MessengerBots::getHandlerSettings(RandomImageBot::class));
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
            'handler' => 'random_image',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['!image'],
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function it_gets_response_and_stores_image_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            config('messenger-bots.random_image_url') => Http::response([]),
        ]);
        $image = MessengerBots::initializeHandler(RandomImageBot::class)
            ->setDataForMessage($thread, $action, $message, null, null);

        $image->handle();

        $this->assertSame(1, Message::image()->count());
        $this->assertFalse($image->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_when_http_fails()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            config('messenger-bots.random_image_url') => Http::response([], 400),
        ]);
        $image = MessengerBots::initializeHandler(RandomImageBot::class)
            ->setDataForMessage($thread, $action, $message, null, null);

        $image->handle();

        $this->assertTrue($image->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_when_store_image_fails()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        Http::fake([
            config('messenger-bots.random_image_url') => Http::response([]),
        ]);
        $this->mock(StoreImageMessage::class)
            ->shouldReceive('execute')
            ->andThrow(new Exception('Error.'));
        $image = MessengerBots::initializeHandler(RandomImageBot::class)
            ->setDataForMessage($thread, $action, $message, null, null);

        $image->handle();

        $this->assertTrue($image->shouldReleaseCooldown());
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
        ]);

        Http::fake([
            config('messenger-bots.random_image_url') => Http::response([]),
        ]);

        MessengerBots::initializeHandler(RandomImageBot::class)
            ->setDataForMessage($thread, $action, $message, null, null)
            ->handle();
    }
}