<?php

namespace RTippin\MessengerBots\Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\BotActionHandler;
use RTippin\MessengerBots\MessengerBotsServiceProvider;
use RTippin\MessengerBots\Tests\Fixtures\UserModel;

class MessengerBotsTestCase extends TestCase
{
    /**
     * @var UserModel
     */
    protected UserModel $tippin;

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

        $config->set('messenger.provider_uuids', env('USE_UUID') === true);
        $config->set('messenger.bots.enabled', true);
        $config->set('messenger.storage.threads.disk', 'messenger');
        $config->set('database.default', 'testbench');
        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        if (env('USE_MORPH_MAPS') === true) {
            Relation::morphMap([
                'users' => UserModel::class,
            ]);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
        $this->artisan('migrate', [
            '--database' => 'testbench',
        ])->run();
        Messenger::registerProviders([
            UserModel::class,
        ]);
        $this->storeTippin();
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

    private function storeTippin(): void
    {
        $this->tippin = UserModel::create([
            'name' => 'Richard Tippin',
            'email' => 'tippindev@gmail.com',
            'password' => 'secret',
        ]);
    }

    protected function createGroupThread(UserModel $admin, ...$participants): Thread
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
