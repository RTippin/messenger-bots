<?php

namespace RTippin\MessengerBots\Bots;

use RTippin\Messenger\Actions\Messages\ArchiveMessage;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Support\BotActionHandler;
use RTippin\Messenger\Support\Helpers;
use Throwable;

class NukeBot extends BotActionHandler
{
    /**
     * @var ArchiveMessage
     */
    private ArchiveMessage $archive;

    /**
     * @param  ArchiveMessage  $archive
     */
    public function __construct(ArchiveMessage $archive)
    {
        $this->archive = $archive;
    }

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'nuke',
            'description' => 'Delete between 5 and 100 past messages, default of 15. [ !nuke {count} ]',
            'name' => 'Nuke Messages',
            'unique' => true,
            'triggers' => ['!nuke'],
            'match' => MessengerBots::MATCH_STARTS_WITH_CASELESS,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        if (! is_null($count = $this->getCount())) {
            $this->nukeMessages($count);

            $this->composer()->message('💣');

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
        $this->composer()->emitTyping()->message('Please select a valid message count between 5 and 100, i.e. ( !nuke 25 )');
    }

    /**
     * @return int|null
     */
    private function getCount(): ?int
    {
        $count = $this->getParsedWords();

        if (is_null($count)) {
            return 15;
        }

        if (count($count) === 1
            && is_numeric($count[0])
            && (int) $count[0] >= 5
            && (int) $count[0] <= 100) {
            return (int) $count[0];
        }

        return null;
    }

    /**
     * @param  int  $count
     * @return void
     *
     * @throws Throwable
     */
    private function nukeMessages(int $count): void
    {
        Messenger::setScopedProvider($this->bot);

        $this->thread
            ->messages()
            ->nonSystem()
            ->latest()
            ->where('created_at', '<=', Helpers::precisionTime($this->message->created_at))
            ->limit($count)
            ->get()
            ->each(fn (Message $message) => $this->archive->execute(
                $this->thread,
                $message
            ));
    }
}
