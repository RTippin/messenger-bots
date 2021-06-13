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
     * @return array
     */
    public function rules(): array
    {
        return [
            'replies' => ['required', 'array', 'min:1'],
            'replies.*' => ['required', 'string'],
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        foreach ($this->decodePayload()['replies'] as $reply) {
            $this->storeMessage->execute($this->message->thread, [
                'message' => $reply,
            ]);
        }
    }
}
