<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class QuotableBot extends BotActionHandler
{
    /**
     * Endpoint we gather data from.
     */
    const API_ENDPOINT = 'https://quote-garden.herokuapp.com/api/v3/quotes/random';

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'quotable',
            'description' => 'Get a random quote.',
            'name' => 'Quotable Quotes',
            'unique' => true,
            'match' => 'exact:caseless',
            'triggers' => ['!quote', '!inspire', '!quotable'],
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $quote = $this->getQuote();

        if ($quote->successful()) {
            $this->sendQuoteMessage($quote->json('data')[0]);

            return;
        }

        $this->releaseCooldown();
    }

    /**
     * @param array $quote
     * @throws Throwable
     */
    private function sendQuoteMessage(array $quote): void
    {
        $this->composer()->emitTyping()->message(":speech_left: \"{$quote['quoteText']}\" - {$quote['quoteAuthor']}");
    }

    /**
     * @return Response
     */
    private function getQuote(): Response
    {
        return Http::acceptJson()->timeout(15)->get(self::API_ENDPOINT);
    }
}
