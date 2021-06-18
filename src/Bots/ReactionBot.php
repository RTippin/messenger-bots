<?php

namespace RTippin\MessengerBots\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\AddReaction;
use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\ReactionException;
use RTippin\Messenger\Rules\HasEmoji;
use Throwable;

class ReactionBot extends BotActionHandler
{
    /**
     * @var AddReaction
     */
    private AddReaction $addReaction;

    /**
     * @var EmojiInterface
     */
    private EmojiInterface $emoji;

    /**
     * ReactionBot constructor.
     *
     * @param AddReaction $addReaction
     * @param EmojiInterface $emoji
     */
    public function __construct(AddReaction $addReaction, EmojiInterface $emoji)
    {
        $this->addReaction = $addReaction;
        $this->emoji = $emoji;
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
            'name' => 'Reaction',
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'reaction' => ['required', new HasEmoji($this->emoji)],
        ];
    }

    /**
     * @param array|null $payload
     * @return string|null
     */
    public function serializePayload(?array $payload): ?string
    {
        $payload['reaction'] = $this->emoji->getValidEmojiShortcodes($payload['reaction'])[0];

        return json_encode($payload);
    }

    /**
     * @throws FeatureDisabledException|ReactionException|Throwable
     */
    public function handle(): void
    {
        $this->addReaction->execute(
            $this->thread,
            $this->message,
            $this->getPayload('reaction')
        );
    }
}
