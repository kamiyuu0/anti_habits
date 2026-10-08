<?php

namespace App\Jobs;

use App\Exceptions\LineApiServerError;
use App\Models\AntiHabit;
use App\Models\User;
use App\Services\LineMessagingClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * 記録を促す LINE 通知を送る。
 * 5xx / 429 はリトライ、それ以外の 4xx は破棄する (Rails 版の retry_on / discard_on 相当)。
 */
class NotifyLineJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $userId, public int $antiHabitId) {}

    /** @return array<int, int> Rails の polynomially_longer に近い待ち時間 (秒) */
    public function backoff(): array
    {
        return [3, 18, 83, 258];
    }

    public function handle(LineMessagingClient $client): void
    {
        $user = User::find($this->userId);
        $antiHabit = AntiHabit::find($this->antiHabitId);

        if (! $user?->uid || ! $antiHabit) {
            return;
        }

        $response = $client->pushText(
            $user->uid,
            "今日の「{$antiHabit->title}」の記録をつけよう！\n\n".config('app.url').'/'
        );

        $status = $response->status();

        if ($status >= 500 || $status === 429) {
            throw new LineApiServerError("LINE API server error: {$status}");
        }

        if ($status >= 400) {
            Log::warning("LINE API client error: {$status}", ['user_id' => $this->userId, 'anti_habit_id' => $this->antiHabitId]);
        }
    }
}
