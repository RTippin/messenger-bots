<?php

namespace RTippin\MessengerBots\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\MessengerBots\Models\Action;

class ActionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Action::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [];
    }
}
