<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Support\Collection;
use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Support\BotActionHandler;
use Throwable;

class ReplyBot extends BotActionHandler
{
    /**
     * @var EmojiInterface
     */
    private EmojiInterface $emoji;

    /**
     * ReplyBot constructor.
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
            'alias' => 'reply',
            'description' => 'Replies with the given response(s).',
            'name' => 'Reply',
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'replies' => ['required', 'array', 'min:1', 'max:5'],
            'replies.*' => ['required', 'string'],
            'quote_original' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array
     */
    public function errorMessages(): array
    {
        return [
            'replies.*.required' => 'Reply is required.',
            'replies.*.string' => 'A reply must be a string.',
        ];
    }

    /**
     * @param  array|null  $payload
     * @return string|null
     */
    public function serializePayload(?array $payload): ?string
    {
        $payload['replies'] = (new Collection($payload['replies']))
            ->transform(fn ($reply) => $this->emoji->toShort($reply))
            ->toArray();

        return json_encode($payload);
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $replies = $this->getPayload('replies');

        $this->composer()->emitTyping();

        foreach ($replies as $key => $reply) {
            if ($key === array_key_first($replies) && $this->getPayload('quote_original')) {
                $this->composer()->message($reply, $this->message->id);

                continue;
            }

            $this->composer()->message($reply);
        }
    }
}
