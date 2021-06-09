<?php

namespace RTippin\MessengerBots\Bots;

use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Contracts\BotHandler;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use Throwable;

class ReplyBot implements BotHandler
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * ReplyBot constructor.
     *
     * @param Messenger $messenger
     * @param StoreMessage $storeMessage
     */
    public function __construct(Messenger $messenger, StoreMessage $storeMessage)
    {
        $this->messenger = $messenger;
        $this->storeMessage = $storeMessage;
    }

    /**
     * @param BotAction $action
     * @param Message $message
     * @param string $matchingTrigger
     * @throws InvalidProviderException
     * @throws Throwable
     */
    public function execute(BotAction $action, Message $message, string $matchingTrigger): void
    {
        $this->messenger->setProvider($action->bot);

        $this->storeMessage->execute($message->thread, [
            'message' => json_decode($action->payload, true)['reply'],
            'reply_to_id' => $message->id,
        ]);
    }
}
