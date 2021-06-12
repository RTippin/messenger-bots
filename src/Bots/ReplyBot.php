<?php

namespace RTippin\MessengerBots\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use Throwable;

class ReplyBot extends BotActionHandler
{
    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * ReplyBot constructor.
     *
     * @param StoreMessage $storeMessage
     */
    public function __construct(StoreMessage $storeMessage)
    {
        $this->storeMessage = $storeMessage;
    }

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'reply',
            'description' => 'Reply with the defined response(s).',
            'name' => 'Reply Bot',
            'unique' => false,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->storeMessage->execute($this->message->thread, [
            'message' => json_decode($this->action->payload, true)['reply'],
            'reply_to_id' => $this->message->id,
        ]);
    }
}
