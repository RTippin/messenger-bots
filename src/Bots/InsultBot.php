<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class InsultBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'insult',
            'description' => 'Responds with a random insult.',
            'name' => 'Insult',
            'unique' => true,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $insult = $this->getInsult();

        if ($insult->successful()) {
            $insult = htmlspecialchars_decode($insult->json('insult'));

            $this->composer()->message("{$this->message->owner->getProviderName()}, $insult");

            return;
        }

        $this->releaseCooldown();
    }

    /**
     * @return Response
     */
    private function getInsult(): Response
    {
        return Http::acceptJson()->timeout(30)->get('https://evilinsult.com/generate_insult.php?lang=en&type=json');
    }
}
