<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Exceptions\FileServiceException;
use RTippin\Messenger\Support\BotActionHandler;
use Throwable;

class RandomImageBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'random_image',
            'description' => 'Get a random image.',
            'name' => 'Random Image',
            'unique' => true,
        ];
    }

    /**
     * @throws Throwable|FileServiceException
     */
    public function handle(): void
    {
        $image = $this->getImage();

        if ($image->failed()) {
            $this->releaseCooldown();

            return;
        }

        $stash = $this->stashImage($image->body());

        try {
            $this->composer()->emitTyping()->image($stash[0]);
        } catch (Throwable $e) {
            report($e);
            $this->releaseCooldown();
        }

        $this->unlinkImage($stash[1]);
    }

    /**
     * @return Response
     */
    private function getImage(): Response
    {
        return Http::timeout(10)->get(config('messenger-bots.random_image_url'));
    }

    /**
     * @param  string  $body
     * @return array
     */
    private function stashImage(string $body): array
    {
        if (static::isTesting()) {
            return [UploadedFile::fake()->image('test.jpg'), 'test.jpg'];
        }

        $name = uniqid();
        $imagePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$name;
        file_put_contents($imagePath, $body);

        return [new UploadedFile($imagePath, $name), $imagePath];
    }

    /**
     * @param  string  $imagePath
     */
    private function unlinkImage(string $imagePath): void
    {
        if (! static::isTesting()) {
            unlink($imagePath);
        }
    }
}
