<?php

namespace RTippin\MessengerBots\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\BotAction;
use Throwable;

class CommandsBot extends BotActionHandler
{
    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * CommandsBot constructor.
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
            'alias' => 'commands',
            'description' => 'List all triggers the current bot has across its actions.',
            'name' => 'List Commands / Triggers',
            'match' => 'exact',
            'triggers' => ['!commands'],
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->storeMessage->execute($this->thread, [
            'message' => "{$this->message->owner->getProviderName()}, I can respond to the following commands:",
        ]);

        $this->storeMessage->execute($this->thread, [
            'message' => $this->getBotActionDetails(),
        ]);
    }

    /**
     * Get all valid actions for the current bot and condense to triggers and name.
     *
     * @return string
     */
    private function getBotActionDetails(): string
    {
        return BotAction::validHandler()
            ->where('bot_id', '=', $this->action->bot_id)
            ->select(['triggers', 'handler'])
            ->get()
            ->transform(function (BotAction $action) {
                return MessengerBots::getHandlerSettings($action->handler)['name'].' - ( '.$action->triggers.' )';
            })
            ->implode(', ');
    }
}
