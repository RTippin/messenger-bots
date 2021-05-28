<?php

namespace RTippin\MessengerBots\Tests;

use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;

trait HelperTrait
{
    protected function createFriends($one, $two): array
    {
        return [
            Friend::factory()->providers($one, $two)->create(),
            Friend::factory()->providers($two, $one)->create(),
        ];
    }

    protected function createPrivateThread($one, $two): Thread
    {
        $private = Thread::factory()->create();
        Participant::factory()
            ->for($private)
            ->owner($one)
            ->create();
        Participant::factory()
            ->for($private)
            ->owner($two)
            ->create();

        return $private;
    }

    protected function createGroupThread($admin, ...$participants): Thread
    {
        $group = Thread::factory()
            ->group()
            ->create([
                'subject' => 'First Test Group',
                'image' => '5.png',
            ]);
        Participant::factory()
            ->for($group)
            ->owner($admin)
            ->admin()
            ->create();

        foreach ($participants as $participant) {
            Participant::factory()
                ->for($group)
                ->owner($participant)
                ->create();
        }

        return $group;
    }

    protected function createMessage($thread, $owner): Message
    {
        return Message::factory()
            ->for($thread)
            ->owner($owner)
            ->create([
                'body' => 'First Test Message',
            ]);
    }
}
