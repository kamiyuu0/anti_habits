<?php

namespace Database\Factories;

use App\Models\AntiHabit;
use App\Models\AntiHabitRecord;
use App\Support\AppTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AntiHabitRecord> */
class AntiHabitRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'anti_habit_id' => AntiHabit::factory(),
            'recorded_on' => AppTime::today()->toDateString(),
        ];
    }
}
