<?php

namespace App\Http\Controllers;

use App\Models\AntiHabit;
use App\Models\NotificationSetting;
use App\Support\AppTime;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    public function create(Request $request, string $antiHabitId)
    {
        $antiHabit = $this->findAntiHabit($request, $antiHabitId);

        $setting = new NotificationSetting(['notification_enabled' => true]);
        $now = AppTime::now();
        $setting->setLocalNotificationTime(sprintf('%02d:%02d', $now->hour, intdiv($now->minute, 5) * 5));

        return view('notification_settings.create', ['antiHabit' => $antiHabit, 'setting' => $setting]);
    }

    public function store(Request $request, string $antiHabitId)
    {
        $antiHabit = $this->findAntiHabit($request, $antiHabitId);
        $validated = $this->validateSetting($request);

        // has_one のため既存の設定があれば置き換える
        $antiHabit->notificationSetting()->delete();

        $setting = $antiHabit->notificationSetting()->make($validated);
        $setting->setLocalNotificationTime($request->input('notification_time'));
        $setting->save();

        return redirect()->route('anti_habits.show', $antiHabit)->with('notice', '通知設定を保存しました。');
    }

    public function edit(Request $request, string $antiHabitId)
    {
        $antiHabit = $this->findAntiHabit($request, $antiHabitId);

        if (! $antiHabit->notificationSetting) {
            return redirect()->route('anti_habits.notification_setting.create', $antiHabit);
        }

        return view('notification_settings.edit', ['antiHabit' => $antiHabit, 'setting' => $antiHabit->notificationSetting]);
    }

    public function update(Request $request, string $antiHabitId)
    {
        $antiHabit = $this->findAntiHabit($request, $antiHabitId);
        $setting = $antiHabit->notificationSetting;

        if (! $setting) {
            return redirect()->route('anti_habits.notification_setting.create', $antiHabit);
        }

        $setting->fill($this->validateSetting($request));
        $setting->setLocalNotificationTime($request->input('notification_time'));
        $setting->save();

        return redirect()->route('anti_habits.show', $antiHabit)->with('notice', '通知設定を更新しました。');
    }

    private function findAntiHabit(Request $request, string $id): AntiHabit
    {
        return $request->user()->antiHabits()->findOrFail($id);
    }

    /** @return array<string, bool> */
    private function validateSetting(Request $request): array
    {
        $request->merge([
            'notification_time' => sprintf('%s:%s', $request->input('notification_hour'), $request->input('notification_minute')),
        ]);

        $request->validate([
            'notification_hour' => ['required', 'date_format:H'],
            'notification_minute' => ['required', 'date_format:i'],
            'notification_time' => ['required', 'date_format:H:i'],
        ]);

        return [
            'notification_enabled' => $request->boolean('notification_enabled'),
            'notify_on_reaction' => $request->boolean('notify_on_reaction'),
            'notify_on_comment' => $request->boolean('notify_on_comment'),
        ];
    }
}
