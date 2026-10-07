<?php

namespace Database\Factories;

use App\Models\AntiHabit;
use App\Models\Bookmark;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Bookmark> */
class BookmarkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'anti_habit_id' => AntiHabit::factory(),
            'user_id' => User::factory(),
        ];
    }
}
