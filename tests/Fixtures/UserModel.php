<?php

namespace RTippin\MessengerBots\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Traits\Messageable;

class UserModel extends User implements MessengerProvider
{
    use Messageable;

    protected $table = 'users';

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        $this->setKeyType(Messenger::shouldUseUuids() ? 'string' : 'int');

        $this->setIncrementing(! Messenger::shouldUseUuids());

        parent::__construct($attributes);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (Messenger::shouldUseUuids()) {
                $model->{$model->getKeyName()} = Str::orderedUuid()->toString();
            }
        });
    }
}
