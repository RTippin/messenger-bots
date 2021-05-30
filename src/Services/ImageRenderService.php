<?php

namespace RTippin\MessengerBots\Services;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Response;
use Illuminate\Routing\ResponseFactory;
use Intervention\Image\ImageManager;
use RTippin\Messenger\Messenger;
use RTippin\MessengerBots\Models\Bot;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageRenderService
{
    /**
     * Extensions we do not want to send through to intervention.
     */
    const IGNORED_EXTENSIONS = [
        'gif',
        'svg',
        'webp',
    ];

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var FilesystemManager
     */
    private FilesystemManager $filesystemManager;

    /**
     * @var ResponseFactory
     */
    private ResponseFactory $responseFactory;

    /**
     * @var ImageManager
     */
    private ImageManager $imageManager;

    /**
     * ImageRenderService constructor.
     *
     * @param Messenger $messenger
     * @param FilesystemManager $filesystemManager
     * @param ResponseFactory $responseFactory
     * @param ImageManager $imageManager
     */
    public function __construct(Messenger $messenger,
                                FilesystemManager $filesystemManager,
                                ResponseFactory $responseFactory,
                                ImageManager $imageManager)
    {
        $this->messenger = $messenger;
        $this->filesystemManager = $filesystemManager;
        $this->responseFactory = $responseFactory;
        $this->imageManager = $imageManager;
    }

    /**
     * @param Bot $bot
     * @param string $size
     * @param string $fileNameChallenge
     * @return StreamedResponse|BinaryFileResponse
     * @throws FileNotFoundException
     */
    public function renderBotAvatar(Bot $bot,
                                    string $size,
                                    string $fileNameChallenge)
    {
        if ($fileNameChallenge !== $bot->avatar) {
            return $this->renderDefaultImage();
        }

        if (! $this->filesystemManager
            ->disk($bot->getStorageDisk())
            ->exists($bot->getAvatarPath())) {
            return $this->renderDefaultImage();
        }

        $extension = pathinfo($this->filesystemManager->disk($bot->getStorageDisk())->path($bot->getAvatarPath()), PATHINFO_EXTENSION);

        if ($this->shouldResize($extension) && $size !== 'lg') {
            return $this->renderImageSize(
                $this->filesystemManager
                    ->disk($bot->getStorageDisk())
                    ->get($bot->getAvatarPath()),
                $size
            );
        }

        return $this->filesystemManager
            ->disk($bot->getStorageDisk())
            ->response($bot->getAvatarPath());
    }

    /**
     * @return BinaryFileResponse
     */
    private function renderDefaultImage(): BinaryFileResponse
    {
//        $default = ! is_null($alias)
//            ? $this->messenger->getProviderDefaultAvatarPath($alias)
//            : null;
//
//        if (! is_null($default) && file_exists($default)) {
//            return $this->responseFactory->file($default);
//        }

        return $this->responseFactory->file($this->messenger->getDefaultNotFoundImage());
    }

    /**
     * @param string $file
     * @param string $size
     * @return BinaryFileResponse|Response
     */
    private function renderImageSize(string $file, string $size)
    {
        try {
            $width = 150;
            $height = 150;

            if ($size === 'md') {
                $width = 300;
                $height = 300;
            }

            ($this->imageManager->make($file)->width() > $this->imageManager->make($file)->height())
                ? $width = null
                : $height = null;

            $resize = $this->imageManager->cache(function ($image) use ($file, $width, $height) {
                return $image->make($file)->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }, 120);

            return $this->imageManager->make($resize)->response();
        } catch (Exception $e) {
            report($e);
        }

        return $this->renderDefaultImage();
    }

    /**
     * @param string $extension
     * @return bool
     */
    private function shouldResize(string $extension): bool
    {
        return ! in_array($extension, self::IGNORED_EXTENSIONS);
    }
}
