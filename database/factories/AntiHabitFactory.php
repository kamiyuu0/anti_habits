<?php

namespace Database\Factories;

use App\Models\AntiHabit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AntiHabit> */
class AntiHabitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => mb_substr(fake()->realText(30), 0, 20),
            'description' => mb_substr(fake()->realText(60), 0, 80),
            'is_public' => true,
            'goal_days' => null,
        ];
    }

    public function private(): static
    {
        return $this->state(fn () => ['is_public' => false]);
    }
}
