<?php

namespace RTippin\MessengerBots\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\AddReaction;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\ReactionException;
use Throwable;

class ReactionBot extends BotActionHandler
{
    /**
     * @var AddReaction
     */
    private AddReaction $addReaction;

    /**
     * ReactionBot constructor.
     *
     * @param AddReaction $addReaction
     */
    public function __construct(AddReaction $addReaction)
    {
        $this->addReaction = $addReaction;
    }

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'react',
            'description' => 'Reacts to a message.',
            'name' => 'Reaction Bot',
            'unique' => false,
        ];
    }

    /**
     * @throws FeatureDisabledException|ReactionException|Throwable
     */
    public function handle(): void
    {
        $reaction = json_decode($this->action->payload, true)['reaction'];

        $this->addReaction->execute($this->message->thread, $this->message, $reaction);
    }
}
