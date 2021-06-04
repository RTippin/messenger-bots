<?php

namespace RTippin\MessengerBots\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\MessengerBots\Tests\Fixtures\UserModel;

class FeatureTestCase extends MessengerBotsTestCase
{
    /**
     * @var MessengerProvider|Model|Authenticatable
     */
    protected $tippin;

    /**
     * @var MessengerProvider|Model|Authenticatable
     */
    protected $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
        $this->artisan('migrate', [
            '--database' => 'testbench',
        ])->run();
        $this->storeBaseUsers();
        BaseMessengerAction::disableEvents();
        Storage::fake('public');
        Storage::fake('messenger');
    }

    protected function tearDown(): void
    {
        Cache::flush();
        BaseMessengerAction::enableEvents();

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $config = $app->get('config');

        $config->set('database.default', 'testbench');
        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    private function storeBaseUsers(): void
    {
        $this->tippin = UserModel::factory()->create([
            'name' => 'Richard Tippin',
            'email' => 'tippindev@gmail.com',
        ]);
        $this->doe = UserModel::factory()->create([
            'name' => 'John Doe',
            'email' => 'doe@example.net',
        ]);
    }
}
