<?php

namespace Tests\Feature\Jobs;

use App\Exceptions\LineApiServerError;
use App\Jobs\NotifyDispatcherJob;
use App\Jobs\NotifyLineJob;
use App\Models\AntiHabit;
use App\Models\AntiHabitRecord;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Services\LineMessagingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotifyJobsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 通知時刻を迎えた未記録の悪習慣だけに通知する(): void
    {
        Queue::fake();
        $this->travelToLocal('2026-10-07 21:05:20'); // UTC 12:05

        $target = AntiHabit::factory()->for(User::factory()->lineLinked())->create();
        NotificationSetting::factory()->for($target)->create(['notification_time' => '12:05:00']);

        $recorded = AntiHabit::factory()->create();
        NotificationSetting::factory()->for($recorded)->create(['notification_time' => '12:05:00']);
        AntiHabitRecord::factory()->for($recorded)->create(['recorded_on' => '2026-10-07']);

        $disabled = AntiHabit::factory()->create();
        NotificationSetting::factory()->for($disabled)->create(['notification_time' => '12:05:00', 'notification_enabled' => false]);

        $otherTime = AntiHabit::factory()->create();
        NotificationSetting::factory()->for($otherTime)->create(['notification_time' => '12:10:00']);

        (new NotifyDispatcherJob)->handle();

        Queue::assertPushed(NotifyLineJob::class, 1);
        Queue::assertPushed(NotifyLineJob::class, fn ($job) => $job->antiHabitId === $target->id && $job->userId === $target->user_id);
    }

    #[Test]
    public function line_にプッシュメッセージを送る(): void
    {
        config(['services.line_messaging.channel_token' => 'token', 'app.url' => 'https://anti-habits.com']);
        Http::fake(['api.line.me/*' => Http::response([], 200)]);
        $user = User::factory()->lineLinked('Uabc')->create();
        $antiHabit = AntiHabit::factory()->for($user)->create(['title' => '夜更かし']);

        (new NotifyLineJob($user->id, $antiHabit->id))->handle(app(LineMessagingClient::class));

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.line.me/v2/bot/message/push'
            && $request->hasHeader('Authorization', 'Bearer token')
            && $request['to'] === 'Uabc'
            && $request['messages'][0]['text'] === "今日の「夜更かし」の記録をつけよう！\n\nhttps://anti-habits.com/");
    }

    #[Test]
    public function サーバーエラーはリトライ対象_クライアントエラーは破棄(): void
    {
        $user = User::factory()->lineLinked()->create();
        $antiHabit = AntiHabit::factory()->for($user)->create();
        $job = new NotifyLineJob($user->id, $antiHabit->id);

        Http::fake(['api.line.me/*' => Http::sequence()->push([], 400)->push([], 429)]);

        $job->handle(app(LineMessagingClient::class)); // 400 は例外にしない

        $this->expectException(LineApiServerError::class);
        $job->handle(app(LineMessagingClient::class));
    }
}
