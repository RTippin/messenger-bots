<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class YoutubeBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'youtube',
            'description' => 'Get the top video results for a youtube search. [ !youtube Stairway To Heaven ]',
            'name' => 'Youtube Videos Search',
            'unique' => true,
            'triggers' => ['!youtube'],
            'match' => 'starts:with:caseless',
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $search = trim(Str::remove($this->matchingTrigger, $this->message->body, false));

        if (! empty($search)) {
            $youtube = $this->getYoutubeSearch($search);

            if ($youtube->successful()) {
                $this->sendYoutubeResultMessages($search, $youtube->json());

                return;
            }
        }

        $this->sendInvalidSelectionMessage();

        $this->releaseCooldown();
    }

    /**
     * @param string $search
     * @param array $results
     * @throws Throwable
     */
    private function sendYoutubeResultMessages(string $search, array $results): void
    {
        $this->composer()->emitTyping()->message("I found the following articles for ( $search ) :");

        foreach ($results as $result) {
            $this->composer()->message($result);
        }
    }

    /**
     * @throws Throwable
     */
    private function sendInvalidSelectionMessage(): void
    {
        $this->composer()->emitTyping()->message('Please select a valid search term, i.e. ( !youtube Stairway To Heaven )');
    }

    /**
     * @param string $search
     * @return Response
     */
    private function getYoutubeSearch(string $search): Response
    {
        return Http::acceptJson()->timeout(15)->get("");
    }
}
