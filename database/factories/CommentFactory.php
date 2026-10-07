<?php

namespace Database\Factories;

use App\Models\AntiHabit;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Comment> */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'anti_habit_id' => AntiHabit::factory(),
            'user_id' => User::factory(),
            'body' => '頑張ってください！',
        ];
    }
}
