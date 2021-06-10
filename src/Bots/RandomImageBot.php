<?php

namespace RTippin\MessengerBots\Bots;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Actions\Messages\StoreImageMessage;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Messenger;
use Throwable;

class RandomImageBot extends BotActionHandler
{
    /**
     * @var string
     */
    public static string $description = 'Get a random image.';

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var StoreImageMessage $storeImage
     */
    private StoreImageMessage $storeImage;

    /**
     * RandomImageBot constructor.
     *
     * @param Messenger $messenger
     * @param StoreImageMessage $storeImage
     */
    public function __construct(Messenger $messenger, StoreImageMessage $storeImage)
    {
        $this->messenger = $messenger;
        $this->storeImage = $storeImage;
    }

    /**
     * @throws InvalidProviderException
     */
    public function handle(): void
    {
        $name = uniqid();
        $file = '/tmp/'.$name;
        file_put_contents($file, Http::timeout(30)->get(config('messenger-bots.random_image_url'))->body());

        $this->messenger->setProvider($this->action->bot);

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
