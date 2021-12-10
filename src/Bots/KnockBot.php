<?php

namespace RTippin\MessengerBots\Bots;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Support\BotActionHandler;
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
            'description' => 'Sends a knock to the group.',
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

        $this->composer()->emitTyping()->message("Knock knock {$this->message->owner->getProviderName()}! :fist::punch::fist::punch:");

        $this->composer()->knock();
    }
}
