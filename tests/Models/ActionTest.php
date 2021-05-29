<?php

namespace RTippin\MessengerBots\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerBots\Models\Bot;
use RTippin\MessengerBots\Models\Action;
use RTippin\MessengerBots\Tests\FeatureTestCase;

class ActionTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $action = Action::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertDatabaseCount('messenger_bot_actions', 1);
        $this->assertDatabaseHas('messenger_bot_actions', [
            'id' => $action->id,
        ]);
        $this->assertInstanceOf(Action::class, $action);
        $this->assertSame(1, Action::count());
    }

    /** @test */
    public function it_has_relations()
    {
        $action = Action::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertSame($action->bot_id, $action->bot->id);
        $this->assertInstanceOf(Bot::class, $action->bot);
        $this->assertSame($this->tippin->getKey(), $action->owner->getKey());
        $this->assertInstanceOf(MessengerProvider::class, $action->owner);
    }

    /** @test */
    public function it_cast_attributes()
    {
        Action::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        $action = Action::first();

        $this->assertInstanceOf(Carbon::class, $action->created_at);
        $this->assertInstanceOf(Carbon::class, $action->updated_at);
    }
}
