<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Support\BotActionHandler;
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
            'description' => 'List all actions and triggers the bot has attached.',
            'name' => 'List Commands',
            'unique' => true,
            'triggers' => ['!commands', '!c'],
            'match' => MessengerBots::MATCH_EXACT_CASELESS,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $actions = $this->getBotActions()
            ->reject(fn (BotAction $action) => $this->adminOnlyActionWhenNotAdmin($action))
            ->transform(fn (BotAction $action) => $this->makeActionString($action))
            ->sort()
            ->chunk(5);

        $this->composer()->emitTyping()->message("{$this->message->owner->getProviderName()}, I can respond to the following commands:");

        foreach ($actions as $action) {
            $this->composer()->message($action->implode(', '));
        }
    }

    /**
     * Get all valid actions for the current bot and condense to trigger's and name.
     *
     * @return Collection
     */
    private function getBotActions(): Collection
    {
        return BotAction::validHandler()
            ->enabled()
            ->where('bot_id', '=', $this->action->bot_id)
            ->select(['triggers', 'handler', 'admin_only'])
            ->get();
    }

    /**
     * @param  BotAction  $action
     * @return bool
     */
    private function adminOnlyActionWhenNotAdmin(BotAction $action): bool
    {
        return $action->admin_only && ! $this->isGroupAdmin;
    }

    /**
     * @param  BotAction  $action
     * @return string
     */
    private function makeActionString(BotAction $action): string
    {
        return $action->getHandler()->name.' - [ '.implode(' | ', $action->getTriggers()).' ]';
    }
}
