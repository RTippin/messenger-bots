<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Support\Collection;
use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Rules\HasEmoji;
use RTippin\Messenger\Support\BotActionHandler;
use RTippin\Messenger\Support\MessengerComposer;
use Throwable;

class ReactionBombBot extends BotActionHandler
{
    /**
     * @var EmojiInterface
     */
    private EmojiInterface $emoji;

    /**
     * ReactionBombBot constructor.
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
            'alias' => 'react_bomb',
            'description' => 'All bots in the thread will add the specified reaction(s) to the message.',
            'name' => 'Reaction Bomb',
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'reactions' => ['required', 'array', 'min:1', 'max:10'],
            'reactions.*' => ['required', new HasEmoji($this->emoji)],
        ];
    }

    /**
     * @param  array|null  $payload
     * @return string|null
     */
    public function serializePayload(?array $payload): ?string
    {
        $payload['reactions'] = Collection::make($payload['reactions'])
            ->map(fn (string $emoji) => $this->emoji->getFirstValidEmojiShortcode($emoji))
            ->unique()
            ->toArray();

        return json_encode($payload);
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $bots = $this->getThreadBots();

        foreach ($this->getPayload('reactions') as $reaction) {
            $bots->each(fn (Bot $bot) => $this->react($bot, $reaction));
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getThreadBots(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->thread
            ->bots()
            ->where('enabled', '=', true)
            ->get();
    }

    /**
     * @param  Bot  $bot
     * @param  string  $reaction
     * @return void
     *
     * @throws Throwable
     */
    private function react(Bot $bot, string $reaction): void
    {
        try {
            app(MessengerComposer::class)
                ->to($this->thread)
                ->from($bot)
                ->reaction($this->message, $reaction);
        } catch (Throwable $e) {
            //Ignore
        }
    }
}
