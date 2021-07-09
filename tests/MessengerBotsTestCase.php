<?php

namespace RTippin\MessengerBots\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\Messenger\Models\Messenger as MessengerModel;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\MessengerBots\MessengerBotsServiceProvider;
use RTippin\MessengerBots\Tests\Fixtures\UserModel;

class MessengerBotsTestCase extends TestCase
{
    /**
     * @var MessengerProvider|UserModel|Authenticatable
     */
    protected $tippin;

    /**
     * @var MessengerProvider|UserModel|Authenticatable
     */
    protected $doe;

    protected function getPackageProviders($app): array
    {
        return [
            MessengerServiceProvider::class,
            MessengerBotsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $config = $app->get('config');

        $config->set('messenger.provider_uuids', false);
        $config->set('messenger.bots.enabled', true);
        $config->set('messenger.providers', $this->getBaseProvidersConfig());
        $config->set('messenger.storage.threads.disk', 'messenger');
        $config->set('messenger-bots.weather_api_key', 'WEATHER-KEY');
        $config->set('messenger-bots.ip_api_key', 'IP-KEY');
        $config->set('messenger-bots.youtube_api_key', 'YOUTUBE-KEY');
        $config->set('messenger-bots.random_image_url', 'IMAGE-URL');
        $config->set('database.default', 'testbench');
        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
        $this->artisan('migrate', [
            '--database' => 'testbench',
        ])->run();
        $this->storeBaseUsers();
        Storage::fake('messenger');
        BaseMessengerAction::disableEvents();
        BotActionHandler::isTesting(true);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        BaseMessengerAction::enableEvents();

        parent::tearDown();
    }

    protected function getBaseProvidersConfig(): array
    {
        return [
            'user' => [
                'model' => UserModel::class,
                'searchable' => false,
                'friendable' => false,
                'devices' => false,
                'default_avatar' => '/path/to/user.png',
                'provider_interactions' => [
                    'can_message' => true,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
        ];
    }

    private function storeBaseUsers(): void
    {
        $this->tippin = UserModel::create([
            'name' => 'Richard Tippin',
            'email' => 'tippindev@gmail.com',
            'password' => 'secret',
        ]);
        $this->doe = UserModel::create([
            'name' => 'John Doe',
            'email' => 'doe@example.net',
            'password' => 'secret',
        ]);
        MessengerModel::factory()->owner($this->tippin)->create();
        MessengerModel::factory()->owner($this->doe)->create();
    }

    protected function createGroupThread($admin, ...$participants): Thread
    {
        $group = Thread::factory()
            ->group()
            ->create([
                'subject' => 'First Test Group',
                'image' => '5.png',
            ]);
        Participant::factory()
            ->for($group)
            ->owner($admin)
            ->admin()
            ->create();

        foreach ($participants as $participant) {
            Participant::factory()
                ->for($group)
                ->owner($participant)
                ->create();
        }

        return $group;
    }
}
