<?php

namespace RTippin\MessengerBots\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use RTippin\Messenger\Actions\Threads\SendKnock;
use RTippin\Messenger\Facades\Messenger;
use Throwable;

class KnockBot extends BotActionHandler
{
    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * @var SendKnock
     */
    private SendKnock $knock;

    /**
     * KnockBot constructor.
     *
     * @param StoreMessage $storeMessage
     * @param SendKnock $knock
     */
    public function __construct(StoreMessage $storeMessage, SendKnock $knock)
    {
        $this->storeMessage = $storeMessage;
        $this->knock = $knock;
    }

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

        $this->storeMessage->execute($this->thread, [
            'message' => "Knock knock {$this->message->owner->getProviderName()}! :fist::punch::fist::punch:",
        ]);

        $this->knock->execute($this->thread);
    }
}
