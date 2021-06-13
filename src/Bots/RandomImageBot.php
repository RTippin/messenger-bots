<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreImageMessage;
use RTippin\Messenger\Exceptions\FileServiceException;
use Throwable;

class RandomImageBot extends BotActionHandler
{
    /**
     * @var StoreImageMessage $storeImage
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
            'alias' => 'image',
            'description' => 'Get a random image.',
            'name' => 'Image Bot',
            'unique' => true,
        ];
    }

    /**
     * @throws Throwable|FileServiceException
     */
    public function handle(): void
    {
        $name = uniqid();
        $file = '/tmp/'.$name;
        file_put_contents($file, Http::timeout(30)->get(config('messenger-bots.random_image_url'))->body());

        try {
            $this->storeImage->execute($this->message->thread, [
                'image' => new UploadedFile($file, $name),
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        unlink($file);
    }
}
