<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Support\Str;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class RockPaperScissorsBot extends BotActionHandler
{
    /**
     * Game rules!
     */
    const Game = [
        'rock' => [
            'weakness' => 'paper',
            'emoji' => ':mountain:',
        ],
        'paper' => [
            'weakness' => 'scissors',
            'emoji' => ':page_facing_up:',
        ],
        'scissors' => [
            'weakness' => 'rock',
            'emoji' => ':scissors:',
        ],
    ];

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'rock_paper_scissors',
            'description' => 'Play a quick game of rock, paper, scissors! [ !rps {rock|paper|scissors} ]',
            'name' => 'Rock Paper Scissors',
            'unique' => true,
            'triggers' => ['!rps'],
            'match' => 'starts:with:caseless',
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        if (! is_null($userChoice = $this->getChoice())) {
            $this->sendGameMessages($userChoice);

            return;
        }

        $this->sendInvalidSelectionMessage();

        $this->releaseCooldown();
    }

    /**
     * @param string $userChoice
     * @throws Throwable
     */
    private function sendGameMessages(string $userChoice): void
    {
        $botChoice = $this->rollBotChoice();

        $this->composer()->message(':mountain: Rock! :page_facing_up: Paper! :scissors: Scissors!');

        $this->composer()->message($this->getRollMessage($botChoice, $userChoice));

        $this->composer()->message($this->getWinningMessage($botChoice, $userChoice));
    }

    /**
     * @throws Throwable
     */
    private function sendInvalidSelectionMessage(): void
    {
        $this->composer()->message('Please select a valid choice, i.e. ( !rps rock|paper|scissors )');
    }

    /**
     * @return array|null
     */
    private function getChoice(): ?string
    {
        $choice = Str::lower(explode(' ', $this->message->body)[1] ?? '');

        if (in_array($choice, array_keys(self::Game))) {
            return $choice;
        }

        return null;
    }

    /**
     * @return string
     */
    private function rollBotChoice(): string
    {
        $roll = rand(1, 99);

        if ($roll < 34) {
            return 'rock';
        }

        if ($roll > 33 && $roll < 67) {
            return 'paper';
        }

        return 'scissors';
    }

    /**
     * @param string $botChoice
     * @param string $userChoice
     * @return string
     */
    private function getRollMessage(string $botChoice, string $userChoice): string
    {
        return self::Game[$botChoice]['emoji'].' :vs: '.self::Game[$userChoice]['emoji'];
    }

    /**
     * @param string $botChoice
     * @param string $userChoice
     * @return string
     */
    private function getWinningMessage(string $botChoice, string $userChoice): string
    {
        if ($botChoice === $userChoice) {
            return "Seems we had a tie {$this->message->owner->getProviderName()}!";
        }

        if (self::Game[$botChoice]['weakness'] === $userChoice) {
            return "{$this->message->owner->getProviderName()} wins!";
        }

        return "I win! {$this->message->owner->getProviderName()} looses!";
    }
}
