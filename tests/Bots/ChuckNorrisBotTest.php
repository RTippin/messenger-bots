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
use RTippin\MessengerBots\Bots\ChuckNorrisBot;
use RTippin\MessengerBots\Tests\MessengerBotsTestCase;

class ChuckNorrisBotTest extends MessengerBotsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MessengerBots::setHandlers([ChuckNorrisBot::class]);
    }

    /** @test */
    public function it_gets_formatted_settings()
    {
        $expected = [
            'alias' => 'chuck',
            'description' => 'Get a random Chuck Norris joke.',
            'name' => 'Chuck Norris',
            'unique' => true,
            'authorize' => false,
            'triggers' => null,
            'match' => null,
        ];

        $this->assertSame($expected, MessengerBots::getHandlerSettings(ChuckNorrisBot::class));
    }

    /** @test */
    public function it_gets_response_and_stores_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $chuck = new ChuckNorrisBot();
        $chuck->setDataForMessage($thread, $action, $message, null, null);
        Http::fake([
            'https://api.chucknorris.io/jokes/random' => Http::response(['value' => 'Chuck!']),
        ]);

        $chuck->handle();

        $this->assertDatabaseHas('messages', [
            'body' => ':skull: Chuck!',
            'owner_type' => 'bots',
        ]);
        $this->assertFalse($chuck->shouldReleaseCooldown());
    }

    /** @test */
    public function it_releases_cooldown_when_http_fails()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $chuck = new ChuckNorrisBot();
        $chuck->setDataForMessage($thread, $action, $message, null, null);
        Http::fake([
            'https://api.chucknorris.io/jokes/random' => Http::response([], 400),
        ]);

        $chuck->handle();

        $this->assertTrue($chuck->shouldReleaseCooldown());
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for(Bot::factory()->for($thread)->owner($this->tippin)->create())->owner($this->tippin)->create();
        $chuck = new ChuckNorrisBot();
        $chuck->setDataForMessage($thread, $action, $message, null, null);
        Http::fake([
            'https://api.chucknorris.io/jokes/random' => Http::response(['value' => 'Chuck!']),
        ]);

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
            Typing::class,
        ]);

        $chuck->handle();
    }
}
