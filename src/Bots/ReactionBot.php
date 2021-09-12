<?php

namespace RTippin\MessengerBots\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\ReactionException;
use RTippin\Messenger\Rules\HasEmoji;
use Throwable;

class ReactionBot extends BotActionHandler
{
    /**
     * @var EmojiInterface
     */
    private EmojiInterface $emoji;

    /**
     * ReactionBot constructor.
     *
     * @param  EmojiInterface  $emoji
     */
    public function __construct(EmojiInterface $emoji)
    {
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
            'description' => 'Adds the specified reaction to a message.',
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
     * @param  array|null  $payload
     * @return string|null
     */
    public function serializePayload(?array $payload): ?string
    {
        $payload['reaction'] = $this->emoji->getFirstValidEmojiShortcode($payload['reaction']);

        return json_encode($payload);
    }

    /**
     * @throws FeatureDisabledException|ReactionException|Throwable
     */
    public function handle(): void
    {
        $this->composer()->reaction($this->message, $this->getPayload('reaction'));
    }
}
