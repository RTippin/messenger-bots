<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Support\Str;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
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
            'match' => 'starts:with:caseless',
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        if (! is_null($numbers = $this->getNumbers())) {
            $this->composer()->message("Rolling ($numbers[0] - $numbers[1]), Got: ".rand($numbers[0], $numbers[1]));

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
        $this->composer()->message('Please select a valid number range, i.e. ( !r 1 50 )');
    }

    /**
     * @return array|null
     */
    private function getNumbers(): ?array
    {
        $base = trim(Str::remove($this->matchingTrigger, $this->message->body, false));

        if (empty($base)) {
            return [0, 100];
        }

        $values = explode(' ', $base);

        if (count($values) === 2
            && is_numeric($values[0])
            && is_numeric($values[1])) {
            return [(int) $values[0], (int) $values[1]];
        }

        return null;
    }
}
