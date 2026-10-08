<?php

namespace App\Jobs;

use App\Models\AntiHabit;
use App\Models\AntiHabitRecord;
use App\Support\AppTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * 通知時刻を迎えた、かつ今日まだ記録していない悪習慣に LINE 通知を送る (5 分ごとに実行)
 */
class NotifyDispatcherJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // notification_time は UTC で保存されている
        $targetTime = now('UTC')->format('H:i:00');

        $recordedToday = AntiHabitRecord::query()
            ->whereDate('recorded_on', AppTime::today()->toDateString())
            ->select('anti_habit_id');

        AntiHabit::query()
            ->whereHas('notificationSetting', fn ($q) => $q
                ->where('notification_enabled', true)
                ->where('notification_time', $targetTime))
            ->whereNotIn('id', $recordedToday)
            ->each(function (AntiHabit $antiHabit) {
                try {
                    NotifyLineJob::dispatch($antiHabit->user_id, $antiHabit->id);
                } catch (Throwable $e) {
                    // sync キュー利用時に 1 件の失敗で他の通知が止まらないようにする
                    report($e);
                }
            });
    }
}
