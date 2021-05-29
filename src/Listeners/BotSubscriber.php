<?php

namespace RTippin\MessengerBots\Listeners;

use Illuminate\Events\Dispatcher;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Messenger;

class BotSubscriber
{
    private Messenger $messenger;

    /**
     * BotSubscriber constructor.
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @param NewMessageEvent $event
     */
    public function newMessageEvent(NewMessageEvent $event): void
    {
        dump($this->messenger->getProvider());
        dump($event->message);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param Dispatcher $events
     * @return void
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            NewMessageEvent::class,
            [BotSubscriber::class, 'newMessageEvent']
        );
    }
}
