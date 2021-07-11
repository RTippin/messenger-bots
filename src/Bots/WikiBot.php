<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class WikiBot extends BotActionHandler
{
    /**
     * Endpoint we gather data from.
     */
    const API_ENDPOINT = 'https://en.wikipedia.org/w/api.php';

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'wiki',
            'description' => 'Get the top results for a wikipedia article search. [ !wiki {search term} ]',
            'name' => 'Wikipedia Search',
            'unique' => true,
            'triggers' => ['!wiki'],
            'match' => 'starts:with:caseless',
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $search = trim(Str::remove($this->matchingTrigger, $this->message->body, false));

        if (! empty($search)) {
            $wiki = $this->getWikiSearch($search);

            if ($wiki->successful()
                && count($results = $this->formatResults($wiki->json()))) {
                $this->sendWikiResultMessages($search, $results);

                return;
            }
        }

        $this->sendInvalidSearchMessage();

        $this->releaseCooldown();
    }

    /**
     * @param string $search
     * @param array $results
     * @throws Throwable
     */
    private function sendWikiResultMessages(string $search, array $results): void
    {
        $this->composer()->emitTyping()->message("I found the following article(s) for ( $search ) :");

        foreach ($results as $result) {
            $this->composer()->message($result);
        }
    }

    /**
     * @throws Throwable
     */
    private function sendInvalidSearchMessage(): void
    {
        $this->composer()->emitTyping()->message('Please select a valid search term, i.e. ( !wiki Computers )');
    }

    /**
     * @param string $search
     * @return Response
     */
    private function getWikiSearch(string $search): Response
    {
        return Http::acceptJson()->timeout(15)->get(self::API_ENDPOINT, [
            'limit' => ($this->getPayload('limit') ?? 3),
            'search' => $search,
            'action' => 'opensearch',
            'namespace' => 0,
            'format' => 'json',
        ]);
    }

    /**
     * Format wiki results. Index 1 contains titles, index 3 contains the links.
     *
     * @param array $results
     * @return array
     */
    private function formatResults(array $results): array
    {
        return (new Collection($results[1]))
            ->map(fn ($value, $key) => $value.' - '.$results[3][$key])
            ->toArray();
    }
}
