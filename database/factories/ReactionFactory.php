<?php

namespace Database\Factories;

use App\Enums\ReactionKind;
use App\Models\AntiHabit;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Reaction> */
class ReactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'anti_habit_id' => AntiHabit::factory(),
            'user_id' => User::factory(),
            'reaction_kind' => ReactionKind::Watching,
        ];
    }
}
