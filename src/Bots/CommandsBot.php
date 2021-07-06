<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\BotAction;
use Throwable;

class CommandsBot extends BotActionHandler
{
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
            'unique' => true,
            'match' => 'exact:caseless',
            'triggers' => ['!commands', '!c'],
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $actions = $this->getBotActions()
            ->transform(fn (BotAction $action) => $this->makeActionString($action))
            ->sort()
            ->chunk(5);

        $this->composer()->emitTyping()->message("{$this->message->owner->getProviderName()}, I can respond to the following commands:");

        foreach ($actions as $action) {
            $this->composer()->message($action->implode(', '));
        }
    }

    /**
     * Get all valid actions for the current bot and condense to triggers and name.
     *
     * @return Collection
     */
    private function getBotActions(): Collection
    {
        return BotAction::validHandler()
            ->where('bot_id', '=', $this->action->bot_id)
            ->select(['triggers', 'handler'])
            ->get();
    }

    /**
     * @param BotAction $action
     * @return string
     */
    private function makeActionString(BotAction $action): string
    {
        return MessengerBots::getHandlerSettings($action->handler)['name'].' - ( '.$action->triggers.' )';
    }
}
