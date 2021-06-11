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
     * Set the alias we will use when attaching the handler to
     * a bot model via a form post.
     *
     * @return string
     */
    public static function getAlias(): string
    {
        return 'image';
    }

    /**
     * Set the description of the handler.
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Get a random image.';
    }

    /**
     * Set the name of the handler we will display to the frontend.
     *
     * @return string
     */
    public static function getName(): string
    {
        return 'Image Bot';
    }

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
