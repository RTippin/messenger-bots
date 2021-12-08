<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Support\Collection;
use RTippin\Messenger\Support\BotActionHandler;
use Throwable;

class KanyeBot extends BotActionHandler
{
    /**
     * Location of our Kanye quotes!
     */
    const KANYE_FILE = __DIR__.'/../../assets/kanye.json';

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'kanye',
            'description' => 'Get a random Kanye West quote.',
            'name' => 'Kanye West',
            'unique' => true,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->composer()->emitTyping()->message(":bearded_person_tone5: \"{$this->getKanyeQuote()}\"");
    }

    /**
     * @return string
     */
    private function getKanyeQuote(): string
    {
        return (new Collection(
            json_decode(
                file_get_contents(self::KANYE_FILE)
            )
        ))->random();
    }
}
