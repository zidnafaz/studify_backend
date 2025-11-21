<?php

namespace Database\Factories;

use App\Models\ClassSchedule;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassSchedule>
 */
class ClassScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ClassSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = fake()->dateTimeBetween('now', '+1 month');
        $endTime = (clone $startTime)->modify('+2 hours');

        return [
            'classroom_id' => Classroom::factory(),
            'coordinator_1' => null,
            'coordinator_2' => null,
            'title' => fake()->sentence(3),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'location' => fake()->optional()->randomElement(['Ruang 101', 'Ruang 201', 'Lab Komputer', 'Aula']),
            'lecturer' => fake()->optional()->name(),
            'description' => fake()->optional()->paragraph(),
            'color' => fake()->randomElement(['#5CD9C1', '#B085CC', '#141458', '#FF5733', '#33FF57']),
        ];
    }

    /**
     * Indicate that the schedule has a coordinator.
     */
    public function withCoordinator(User $coordinator, int $position = 1): static
    {
        return $this->state(fn (array $attributes) => [
            $position === 1 ? 'coordinator_1' : 'coordinator_2' => $coordinator->id,
        ]);
    }

    /**
     * Indicate that the schedule belongs to a specific classroom.
     */
    public function forClassroom(Classroom $classroom): static
    {
        return $this->state(fn (array $attributes) => [
            'classroom_id' => $classroom->id,
        ]);
    }
}
