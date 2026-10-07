<?php

namespace Database\Factories;

use App\Models\AntiHabit;
use App\Models\NotificationSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotificationSetting> */
class NotificationSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'anti_habit_id' => AntiHabit::factory(),
            'notification_time' => '12:00:00', // UTC (JST 21:00)
            'notification_enabled' => true,
            'notify_on_reaction' => false,
            'notify_on_comment' => false,
        ];
    }
}
