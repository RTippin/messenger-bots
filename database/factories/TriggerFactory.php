<?php

namespace RTippin\MessengerBots\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\MessengerBots\Models\Trigger;

class TriggerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Trigger::class;

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
