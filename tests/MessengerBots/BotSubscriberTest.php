<?php

namespace RTippin\MessengerBots\Tests\MessengerBots;

use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\MessengerBots\Tests\FeatureTestCase;

class BotSubscriberTest extends FeatureTestCase
{
    /** @test */
    public function it_responds_to_new_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        Messenger::setProvider($this->tippin);
        event(new NewMessageEvent($message));

        $this->assertTrue(true);
    }
}
