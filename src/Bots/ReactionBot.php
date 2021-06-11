<?php

namespace RTippin\MessengerBots\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\AddReaction;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Messenger;
use Throwable;

class ReactionBot extends BotActionHandler
{
    /**
     * Set the alias we will use when attaching the handler to
     * a bot model via a form post.
     *
     * @return string
     */
    public static function getAlias(): string
    {
        return 'react';
    }

    /**
     * Set the description of the handler.
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Reacts to a message.';
    }

    /**
     * Set the name of the handler we will display to the frontend.
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'Reaction Bot';
    }

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var AddReaction
     */
    private AddReaction $addReaction;

    /**
     * ReactionBot constructor.
     *
     * @param Messenger $messenger
     * @param AddReaction $addReaction
     */
    public function __construct(Messenger $messenger, AddReaction $addReaction)
    {
        $this->messenger = $messenger;
        $this->addReaction = $addReaction;
    }

    /**
     * @throws InvalidProviderException
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->messenger->setProvider($this->action->bot);

        $reaction = json_decode($this->action->payload, true)['reaction'];

        $this->addReaction->execute($this->message->thread, $this->message, $reaction);
    }
}
