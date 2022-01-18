<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Support\Collection;
use RTippin\Messenger\Support\BotActionHandler;
use Throwable;

class YoMommaBot extends BotActionHandler
{
    /**
     * Location of our yo-momma jokes!
     */
    const JOKES_FILE = __DIR__.'/../../assets/mom-jokes.json';

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'yomomma',
            'description' => 'Get a random yo-momma joke.',
            'name' => 'Yo Momma',
            'unique' => true,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->composer()->emitTyping()->message("👩 {$this->getYoMomma()}");
    }

    /**
     * Pick a random joke from our yo-momma jokes file.
     *
     * @return string
     */
    public function getYoMomma(): string
    {
        return Collection::make(
            json_decode(
                file_get_contents(self::JOKES_FILE)
            )
        )->random();
    }
}
