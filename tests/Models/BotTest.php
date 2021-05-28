<?php

namespace RTippin\MessengerBots\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerBots\Models\Bot;
use RTippin\MessengerBots\Tests\FeatureTestCase;

class BotTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        $this->assertDatabaseCount('messenger_bots', 1);
        $this->assertDatabaseHas('messenger_bots', [
            'id' => $bot->id,
        ]);
        $this->assertInstanceOf(Bot::class, $bot);
        $this->assertSame(1, Bot::count());
    }

    /** @test */
    public function it_has_relations()
    {
        $bot = Bot::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create();

        $this->assertSame($bot->thread_id, $bot->thread->id);
        $this->assertSame($this->tippin->getKey(), $bot->owner->getKey());
        $this->assertInstanceOf(Thread::class, $bot->thread);
        $this->assertInstanceOf(MessengerProvider::class, $bot->owner);
    }

    /** @test */
    public function owner_returns_ghost_if_not_found()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->create([
            'owner_id' => 404,
            'owner_type' => $this->tippin->getMorphClass(),
        ]);

        $this->assertInstanceOf(GhostUser::class, $bot->owner);
    }

    /** @test */
    public function it_cast_attributes()
    {
        Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();
        $bot = Bot::first();

        $this->assertInstanceOf(Carbon::class, $bot->created_at);
        $this->assertInstanceOf(Carbon::class, $bot->updated_at);
    }
}
