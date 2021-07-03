<?php

namespace RTippin\MessengerBots\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Facades\Messenger;
use Throwable;

class KnockBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'knock',
            'description' => 'Have the bot send a knock at the thread.',
            'name' => 'Knock Knock',
            'unique' => true,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        Messenger::setKnockTimeout(0);

        $this->composer()->message("Knock knock {$this->message->owner->getProviderName()}! :fist::punch::fist::punch:");

        $this->composer()->knock();
    }
}
