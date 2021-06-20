<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreImageMessage;
use RTippin\Messenger\Exceptions\FileServiceException;
use Throwable;

class RandomImageBot extends BotActionHandler
{
    /**
     * @var StoreImageMessage
     */
    private StoreImageMessage $storeImage;

    /**
     * RandomImageBot constructor.
     *
     * @param StoreImageMessage $storeImage
     */
    public function __construct(StoreImageMessage $storeImage)
    {
        $this->storeImage = $storeImage;
    }

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

        if ($image->successful()) {
            $name = uniqid();
            $imagePath = '/tmp/'.$name;
            file_put_contents($imagePath, $image->body());

            try {
                $this->storeImage->execute($this->thread, [
                    'image' => new UploadedFile($imagePath, $name),
                ]);
            } catch (Throwable $e) {
                $this->releaseCooldown();
            }

            unlink($imagePath);

            return;
        }

        $this->releaseCooldown();
    }

    /**
     * @return Response
     */
    private function getImage(): Response
    {
        return Http::timeout(30)->get(config('messenger-bots.random_image_url'));
    }
}
