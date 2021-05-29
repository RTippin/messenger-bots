<?php

namespace RTippin\MessengerBots\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\ImageRenderService;
use RTippin\MessengerBots\Models\Bot;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RenderBotAvatar
{
    use AuthorizesRequests;

    /**
     * Render group avatar.
     *
     * @param ImageRenderService $service
     * @param Thread $thread
     * @param Bot $bot
     * @param string $size
     * @param string $image
     * @return StreamedResponse|BinaryFileResponse
     */
    public function __invoke(ImageRenderService $service,
                             Thread $thread,
                             Bot $bot,
                             string $size,
                             string $image)
    {
//        $this->authorize('groupMethod', $thread);

        return null;
    }
}
