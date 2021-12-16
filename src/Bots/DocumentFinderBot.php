<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Support\BotActionHandler;
use Throwable;

class DocumentFinderBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'document_finder',
            'description' => 'Search the group for uploaded documents. [ !document {search} ]',
            'name' => 'Document Finder',
            'unique' => true,
            'triggers' => ['!document', '!doc'],
            'match' => MessengerBots::MATCH_STARTS_WITH_CASELESS,
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
        $search = $this->getParsedMessage();

        if (! is_null($search)) {
            $documents = $this->searchDocuments($search);

            if (! $documents->count()) {
                $this->sendNoResultsMessage($search);

                return;
            }

            $this->sendDocumentResultsMessages($documents, $search);

            return;
        }

        $this->sendInvalidSearchMessage();

        $this->releaseCooldown();
    }

    /**
     * @throws Throwable
     */
    private function sendInvalidSearchMessage(): void
    {
        $this->composer()->emitTyping()->message('Please select a valid search term, i.e. ( !document resume )');
    }

    /**
     * @throws Throwable
     */
    private function sendNoResultsMessage(string $search): void
    {
        $this->composer()->emitTyping()->message("I didn't find any document(s) matching ( $search )");
    }

    /**
     * @param  Collection  $documents
     * @param  string  $search
     *
     * @throws Throwable
     */
    private function sendDocumentResultsMessages(Collection $documents, string $search): void
    {
        Messenger::shouldUseAbsoluteRoutes(true);

        $this->composer()->emitTyping()->message("I found the following document(s) matching ( $search ) :");

        $documents->each(fn (Message $document) => $this->sendDocumentMessage($document));
    }

    /**
     * @param  Message  $document
     *
     * @throws Throwable
     */
    private function sendDocumentMessage(Message $document): void
    {
        $this->composer()->message(":floppy_disk: $document->body - {$document->getDocumentDownloadRoute()}");
    }

    /**
     * @param  string  $search
     * @return Collection|Message[]
     */
    private function searchDocuments(string $search): Collection
    {
        return $this->thread
            ->documents()
            ->latest()
            ->where('body', 'LIKE', "%$search%")
            ->limit($this->getPayload('limit') ?? 5)
            ->get();
    }
}
