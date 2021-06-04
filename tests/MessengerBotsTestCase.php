<?php

namespace RTippin\MessengerBots\Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use Orchestra\Testbench\TestCase;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\MessengerBots\MessengerBotsServiceProvider;
use RTippin\MessengerBots\Tests\Fixtures\UserModel;

class MessengerBotsTestCase extends TestCase
{
    use HelperTrait;

    /**
     * Set TRUE to run all feature test with
     * relation morph map set for providers.
     */
    const UseMorphMap = true;

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
        $config->set('messenger.calling.enabled', true);
        $config->set('messenger.storage.avatars.disk', 'public');
        $config->set('messenger.storage.threads.disk', 'messenger');
        $config->set('messenger.calling.enabled', true);
        $config->set('messenger.providers', $this->getBaseProvidersConfig());
        $config->set('messenger.site_name', 'Messenger-Testbench');

        if (self::UseMorphMap) {
            Relation::morphMap([
                'users' => UserModel::class,
            ]);
        }
    }

    protected function getBaseProvidersConfig(): array
    {
        return [
            'user' => [
                'model' => UserModel::class,
                'searchable' => true,
                'friendable' => true,
                'devices' => true,
                'default_avatar' => '/path/to/user.png',
                'provider_interactions' => [
                    'can_message' => true,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
        ];
    }
}
