<?php

namespace RTippin\MessengerBots\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerBots\Models\Bot;
use RTippin\MessengerBots\Models\Trigger;
use RTippin\MessengerBots\Tests\FeatureTestCase;

class TriggerTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $trigger = Trigger::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->create();

        $this->assertDatabaseCount('messenger_bot_triggers', 1);
        $this->assertDatabaseHas('messenger_bot_triggers', [
            'id' => $trigger->id,
        ]);
        $this->assertInstanceOf(Trigger::class, $trigger);
        $this->assertSame(1, Trigger::count());
    }

    /** @test */
    public function it_has_relations()
    {
        $trigger = Trigger::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->create();

        $this->assertSame($trigger->bot_id, $trigger->bot->id);
        $this->assertInstanceOf(Bot::class, $trigger->bot);
    }

    /** @test */
    public function it_cast_attributes()
    {
        Trigger::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->create();
        $trigger = Trigger::first();

        $this->assertInstanceOf(Carbon::class, $trigger->created_at);
        $this->assertInstanceOf(Carbon::class, $trigger->updated_at);
    }
}
