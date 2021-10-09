<?php

namespace RTippin\MessengerBots\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\MessengerBots;
use Throwable;

class RollBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'roll',
            'description' => 'Rolls a random number between 0 and 100. You may also specify the number range to roll between. [ !roll {start} {end} ]',
            'name' => 'Roll Numbers',
            'unique' => true,
            'triggers' => ['!r', '!roll'],
            'match' => MessengerBots::MATCH_STARTS_WITH_CASELESS,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        if (! is_null($numbers = $this->getNumbers())) {
            $this->composer()->emitTyping()->message("Rolling ($numbers[0] - $numbers[1]), Got: ".rand($numbers[0], $numbers[1]));

            return;
        }

        $this->sendInvalidSelectionMessage();

        $this->releaseCooldown();
    }

    /**
     * @throws Throwable
     */
    private function sendInvalidSelectionMessage(): void
    {
        $this->composer()->emitTyping()->message('Please select a valid number range, i.e. ( !r 1 50 )');
    }

    /**
     * @return array|null
     */
    private function getNumbers(): ?array
    {
        $choices = $this->getParsedWords();

        if (is_null($choices)) {
            return [0, 100];
        }

        if (count($choices) === 2
            && is_numeric($choices[0])
            && is_numeric($choices[1])) {
            return [(int) $choices[0], (int) $choices[1]];
        }

        return null;
    }
}
