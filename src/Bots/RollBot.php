<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Support\Str;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreMessage;
use Throwable;

class RollBot extends BotActionHandler
{
    /**
     * @var StoreMessage
     */
    private StoreMessage $storeMessage;

    /**
     * RollBot constructor.
     *
     * @param StoreMessage $storeMessage
     */
    public function __construct(StoreMessage $storeMessage)
    {
        $this->storeMessage = $storeMessage;
    }

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
            $this->storeMessage->execute($this->thread, [
                'message' => "Rolling ($numbers[0] - $numbers[1]), Got: ".rand($numbers[0], $numbers[1]),
            ]);

            return;
        }

        $this->releaseCooldown();
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
