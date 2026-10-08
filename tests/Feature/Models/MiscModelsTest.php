<?php

namespace Tests\Feature\Models;

use App\Models\AntiHabit;
use App\Models\Comment;
use App\Models\NotificationSetting;
use App\Models\Tag;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MiscModelsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function コメント数は_counter_cache_として更新される(): void
    {
        $antiHabit = AntiHabit::factory()->create();
        $updatedAt = $antiHabit->fresh()->updated_at;

        $comments = Comment::factory()->count(2)->for($antiHabit)->create();
        $this->assertSame(2, $antiHabit->fresh()->comments_count);

        $comments->first()->delete();
        $this->assertSame(1, $antiHabit->fresh()->comments_count);
        // Rails の counter_cache と同様に updated_at は変えない
        $this->assertEquals($updatedAt, $antiHabit->fresh()->updated_at);
    }

    #[Test]
    public function 通知時刻は日本時間で入力し_utc_で保存する(): void
    {
        $setting = NotificationSetting::factory()->make();
        $setting->setLocalNotificationTime('21:05');
        $setting->save();

        $this->assertDatabaseHas('notification_settings', ['id' => $setting->id, 'notification_time' => '12:05:00']);
        $this->assertSame('21:05', $setting->fresh()->localNotificationTime());

        $setting->setLocalNotificationTime('07:30');
        $setting->save();
        $this->assertDatabaseHas('notification_settings', ['id' => $setting->id, 'notification_time' => '22:30:00']);
        $this->assertSame('07:30', $setting->fresh()->localNotificationTime());
    }

    #[Test]
    public function find_or_create_by_names_は空文字を除外し既存タグを再利用する(): void
    {
        $existing = Tag::factory()->create(['name' => '既存']);

        $tags = Tag::findOrCreateByNames([' 既存 ', '', '新規', '  ']);

        $this->assertSame(['既存', '新規'], $tags->pluck('name')->all());
        $this->assertTrue($tags->first()->is($existing));
        $this->assertSame(2, Tag::count());
    }

    #[Test]
    public function 日時は_rails_と同じく_utc_のマイクロ秒精度で保存される(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-07 09:00:00.123456', 'Asia/Tokyo'));
        $antiHabit = AntiHabit::factory()->create();

        $raw = \DB::table('anti_habits')->where('id', $antiHabit->id)->value('created_at');
        $this->assertSame('2026-10-07 00:00:00.123456', $raw);
    }
}
