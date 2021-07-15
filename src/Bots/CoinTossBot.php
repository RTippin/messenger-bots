<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Support\Str;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class CoinTossBot extends BotActionHandler
{
    /**
     * Coins and their emojis.
     */
    const Coins = [
        'heads' => ':cd:',
        'tails' => ':dvd:',
    ];

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'coin_toss',
            'description' => 'Toss a coin! Simple heads or tails. [ !toss {heads|tails} ]',
            'name' => 'Coin Toss',
            'unique' => true,
            'triggers' => ['!toss', '!headsOrTails', '!coinToss'],
            'match' => 'starts:with:caseless',
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        if (! is_null($userChoice = $this->getChoice())) {
            $this->sendCoinTossMessages($userChoice);

            return;
        }

        $this->sendInvalidSelectionMessage();

        $this->releaseCooldown();
    }

    /**
     * @param string $userChoice
     * @throws Throwable
     */
    private function sendCoinTossMessages(string $userChoice): void
    {
        $botChoice = $this->tossBotChoice();

        $this->composer()->emitTyping()->message(':cd: Heads or :dvd: Tails!');

        if (empty($userChoice)) {
            $this->composer()->message($this->getRollMessage($botChoice));

            return;
        }

        $this->composer()->message($this->getChoiceRollMessage($botChoice, $userChoice));

        $this->composer()->message($this->getChoiceOutcomeMessage($botChoice, $userChoice));
    }

    /**
     * @throws Throwable
     */
    private function sendInvalidSelectionMessage(): void
    {
        $this->composer()->emitTyping()->message('Please select a valid choice, i.e. ( !toss {heads|tails} )');
    }

    /**
     * @return array|null
     */
    private function getChoice(): ?string
    {
        $choice = Str::lower(explode(' ', $this->message->body)[1] ?? '');

        if (empty($choice)) {
            return '';
        }

        if (in_array($choice, array_keys(self::Coins))) {
            return $choice;
        }

        return null;
    }

    /**
     * @return string
     */
    private function tossBotChoice(): string
    {
        return rand(1, 99) < 50 ? 'heads' : 'tails';
    }

    /**
     * @param string $botChoice
     * @param string $userChoice
     * @return string
     */
    private function getChoiceRollMessage(string $botChoice, string $userChoice): string
    {
        return "{$this->message->owner->getProviderName()} chose $userChoice ".self::Coins[$userChoice]." *Toss* We got $botChoice ".self::Coins[$botChoice];
    }

    /**
     * @param string $botChoice
     * @param string $userChoice
     * @return string
     */
    private function getChoiceOutcomeMessage(string $botChoice, string $userChoice): string
    {
        if ($botChoice === $userChoice) {
            return "{$this->message->owner->getProviderName()} wins!";
        }

        return "{$this->message->owner->getProviderName()} looses!";
    }

    /**
     * @param string $botChoice
     * @return string
     */
    private function getRollMessage(string $botChoice): string
    {
        return "*Toss* We got $botChoice ".self::Coins[$botChoice];
    }
}
