<?php

namespace Database\Factories;

use App\Models\PersonalSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonalScheduleFactory extends Factory
{
    protected $model = PersonalSchedule::class;

    public function definition(): array
    {
        $startTime = $this->faker->dateTimeBetween('+1 day', '+5 days');
        $endTime = (clone $startTime)->modify('+2 hours');

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'location' => $this->faker->city,
            'description' => $this->faker->sentence(),
            'color' => sprintf('#%06X', $this->faker->numberBetween(0, 0xFFFFFF)),
        ];
    }
}

