<?php

namespace Database\Factories;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceTokenFactory extends Factory
{
    protected $model = DeviceToken::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'token' => $this->faker->uuid,
            'platform' => $this->faker->randomElement(['android', 'ios', 'web']),
        ];
    }
}
