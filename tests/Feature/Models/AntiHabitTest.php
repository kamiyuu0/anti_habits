<?php

namespace Tests\Feature\Models;

use App\Models\AntiHabit;
use App\Models\AntiHabitRecord;
use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\NotificationSetting;
use App\Models\Reaction;
use App\Models\Tag;
use App\Models\User;
use App\Support\AppTime;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AntiHabitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 水曜日 (週の途中) に固定
        $this->travelToLocal('2026-10-07 12:00:00');
    }

    private function record(AntiHabit $antiHabit, int $daysAgo): AntiHabitRecord
    {
        return AntiHabitRecord::factory()->for($antiHabit)->create([
            'recorded_on' => AppTime::today()->subDays($daysAgo)->toDateString(),
        ]);
    }

    // ---- scopes ----

    #[Test]
    public function tagged_with_は指定したタグを持つものだけを返す(): void
    {
        $tag1 = Tag::factory()->create(['name' => 'タグ1']);
        $tag2 = Tag::factory()->create(['name' => 'タグ2']);
        $a1 = AntiHabit::factory()->hasAttached($tag1)->create();
        $a2 = AntiHabit::factory()->hasAttached($tag2)->create();
        $a3 = AntiHabit::factory()->hasAttached([$tag1, $tag2])->create();

        $ids = AntiHabit::taggedWith('タグ1')->pluck('id');

        $this->assertEqualsCanonicalizing([$a1->id, $a3->id], $ids->all());
        $this->assertNotContains($a2->id, $ids);
        $this->assertCount(0, AntiHabit::taggedWith('存在しないタグ')->get());
    }

    #[Test]
    public function publicly_visible_は公開中のものだけを返す(): void
    {
        $public = AntiHabit::factory()->count(2)->create();
        $private = AntiHabit::factory()->private()->create();

        $ids = AntiHabit::publiclyVisible()->pluck('id');

        $this->assertEqualsCanonicalizing($public->pluck('id')->all(), $ids->all());
        $this->assertNotContains($private->id, $ids);
    }

    // ---- today_record / consecutive_days_achieved ----

    #[Test]
    public function today_record_は今日の記録だけを返す(): void
    {
        $antiHabit = AntiHabit::factory()->create();
        $this->assertNull($antiHabit->todayRecord());

        $this->record($antiHabit, 1);
        $this->assertNull($antiHabit->todayRecord());

        $today = $this->record($antiHabit, 0);
        $this->assertTrue($today->is($antiHabit->todayRecord()));
    }

    /** @return array<string, array{0: array<int, int>, 1: int}> */
    public static function consecutiveDaysProvider(): array
    {
        return [
            '記録なし' => [[], 0],
            '今日のみ' => [[0], 1],
            '昨日のみ' => [[1], 1],
            '今日と昨日' => [[0, 1], 2],
            '3日連続' => [[0, 1, 2], 3],
            '途中で途切れている' => [[0, 1, 3, 4], 2],
            '一昨日まで連続・昨日なし・今日あり' => [[0, 2, 3, 4], 1],
            '過去に長期連続・最近途切れた' => [[1, 2, 4, 5, 6, 7, 8, 9], 2],
        ];
    }

    #[Test]
    #[DataProvider('consecutiveDaysProvider')]
    public function consecutive_days_achieved_は今日または昨日から遡った連続日数を返す(array $daysAgo, int $expected): void
    {
        $antiHabit = AntiHabit::factory()->create();
        foreach ($daysAgo as $d) {
            $this->record($antiHabit, $d);
        }

        $this->assertSame($expected, $antiHabit->consecutiveDaysAchieved());
    }

    #[Test]
    public function 日付の判定は日本時間で行う(): void
    {
        // UTC では 10/6 だが日本時間では 10/7 の 0:30
        $this->travelToLocal('2026-10-07 00:30:00');
        $antiHabit = AntiHabit::factory()->create();
        AntiHabitRecord::factory()->for($antiHabit)->create(['recorded_on' => '2026-10-07']);

        $this->assertNotNull($antiHabit->todayRecord());
        $this->assertSame(1, $antiHabit->consecutiveDaysAchieved());
    }

    // ---- goal ----

    #[Test]
    public function goal_reached_は連続日数が目標以上かを返す(): void
    {
        $antiHabit = AntiHabit::factory()->create(['goal_days' => 5]);
        $antiHabit->goal_days = null;
        $this->assertFalse($antiHabit->goalReached());

        $antiHabit->goal_days = 5;
        foreach (range(0, 1) as $d) {
            $this->record($antiHabit, $d);
        }
        $this->assertFalse($antiHabit->goalReached());

        foreach (range(2, 4) as $d) {
            $this->record($antiHabit, $d);
        }
        $this->assertTrue($antiHabit->goalReached());

        foreach (range(5, 6) as $d) {
            $this->record($antiHabit, $d);
        }
        $this->assertTrue($antiHabit->goalReached());
    }

    #[Test]
    public function 記録の追加で目標達成フラグが更新される(): void
    {
        $antiHabit = AntiHabit::factory()->create(['goal_days' => 3]);
        $this->record($antiHabit, 2);
        $this->record($antiHabit, 1);
        $this->assertFalse($antiHabit->fresh()->goal_achieved);

        $this->record($antiHabit, 0);
        $this->assertTrue($antiHabit->fresh()->goal_achieved);

        // 目標達成後に記録を追加しても true のまま
        $this->travelToLocal('2026-10-08 12:00:00');
        $this->record($antiHabit, 0);
        $this->assertTrue($antiHabit->fresh()->goal_achieved);
    }

    #[Test]
    public function 記録の削除で連続が途切れると目標達成フラグが外れる(): void
    {
        $antiHabit = AntiHabit::factory()->create(['goal_days' => 2]);
        $this->record($antiHabit, 1);
        $today = $this->record($antiHabit, 0);
        $this->assertTrue($antiHabit->fresh()->goal_achieved);

        // 今日の記録を削除すると連続は昨日の 1 日のみになる
        $today->delete();

        $this->assertFalse($antiHabit->fresh()->goal_achieved);
    }

    #[Test]
    public function 目標日数を変更すると最新の連続日数で再判定される(): void
    {
        $antiHabit = AntiHabit::factory()->create(['goal_days' => 2]);
        $this->record($antiHabit, 1);
        $this->record($antiHabit, 0);
        $this->assertTrue($antiHabit->fresh()->goal_achieved);

        $antiHabit->fresh()->update(['goal_days' => 10]);
        $this->assertFalse($antiHabit->fresh()->goal_achieved);

        $antiHabit->fresh()->update(['goal_days' => null]);
        $this->assertFalse($antiHabit->fresh()->goal_achieved);
    }

    #[Test]
    public function goal_days_が_null_の場合は目標達成フラグを更新しない(): void
    {
        $antiHabit = AntiHabit::factory()->create(['goal_days' => null]);
        $this->record($antiHabit, 0);

        $this->assertFalse($antiHabit->fresh()->goal_achieved);
    }

    // ---- calendar_data ----

    #[Test]
    public function calendar_data_は指定日数分の記録有無を返す(): void
    {
        $antiHabit = AntiHabit::factory()->create();
        $this->record($antiHabit, 0);
        $this->record($antiHabit, 2);

        $data = $antiHabit->calendarData();
        $this->assertCount(90, $data);
        $this->assertSame('2026-07-10', $data[0][0]);
        $this->assertSame('2026-10-07', $data[89][0]);

        $map = collect($data)->mapWithKeys(fn ($d) => [$d[0] => $d[1]]);
        $this->assertSame(1, $map['2026-10-07']);
        $this->assertSame(0, $map['2026-10-06']);
        $this->assertSame(1, $map['2026-10-05']);

        $this->assertCount(30, $antiHabit->calendarData(30));
    }

    // ---- tags ----

    #[Test]
    public function tag_names_as_string_はカンマ区切りで返す(): void
    {
        $antiHabit = AntiHabit::factory()->create();
        $this->assertSame('', $antiHabit->tagNamesAsString());

        $antiHabit->syncTagNames('タグ1, タグ2, タグ3');
        $this->assertSame('タグ1, タグ2, タグ3', $antiHabit->fresh()->tagNamesAsString());
    }

    #[Test]
    public function sync_tag_names_は既存タグを再利用し空白をトリムして置き換える(): void
    {
        $existing = Tag::factory()->create(['name' => 'タグ1']);
        $antiHabit = AntiHabit::factory()->create();

        $antiHabit->syncTagNames(' タグ1 ,  タグ2 ,, ');
        $this->assertSame(2, Tag::count());
        $this->assertEqualsCanonicalizing(['タグ1', 'タグ2'], $antiHabit->tags()->pluck('name')->all());
        $this->assertTrue($antiHabit->tags->contains($existing));

        $antiHabit->syncTagNames('タグ2, タグ3');
        $this->assertEqualsCanonicalizing(['タグ2', 'タグ3'], $antiHabit->tags()->pluck('name')->all());
    }

    // ---- ranking ----

    #[Test]
    public function ランキングはデータがなければ空(): void
    {
        $this->assertSame([], AntiHabit::topWeeklyAchieversWithRanks());

        // 先週の記録は対象外 (2026-10-05 が月曜)
        $antiHabit = AntiHabit::factory()->create();
        AntiHabitRecord::factory()->for($antiHabit)->create(['recorded_on' => '2026-10-04']);
        $this->assertSame([], AntiHabit::topWeeklyAchieversWithRanks());
    }

    #[Test]
    public function ランキングは公開中のもののみ対象(): void
    {
        $public = AntiHabit::factory()->create();
        $private = AntiHabit::factory()->private()->create();
        $this->record($public, 0);
        $this->record($private, 0);

        $result = AntiHabit::topWeeklyAchieversWithRanks();

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]['anti_habits']->contains($public));
        $this->assertFalse($result[0]['anti_habits']->contains($private));
    }

    /** 今週 (月曜〜今日) に指定日数分の記録を持つ悪習慣を作る */
    private function achiever(int $days, ?string $createdAt = null): AntiHabit
    {
        $antiHabit = AntiHabit::factory()->create($createdAt ? ['created_at' => $createdAt] : []);
        foreach (range(0, $days - 1) as $d) {
            AntiHabitRecord::factory()->for($antiHabit)->create(['recorded_on' => AppTime::today()->startOfWeek(CarbonInterface::MONDAY)->addDays($d)->toDateString()]);
        }

        return $antiHabit;
    }

    #[Test]
    public function 一位が3件以上なら一位のみ(): void
    {
        $this->travelToLocal('2026-10-09 12:00:00'); // 金曜
        foreach (range(1, 3) as $_) {
            $this->achiever(5);
        }
        $this->achiever(3);

        $result = AntiHabit::topWeeklyAchieversWithRanks();

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['rank']);
        $this->assertSame(5, $result[0]['weekly_days']);
        $this->assertCount(3, $result[0]['anti_habits']);
    }

    #[Test]
    public function 一位と二位で3件以上なら二位まで_順位は人数分繰り下がる(): void
    {
        $this->travelToLocal('2026-10-09 12:00:00');
        $this->achiever(5);
        $this->achiever(5);
        $this->achiever(3);
        $this->achiever(3);
        $this->achiever(1);

        $result = AntiHabit::topWeeklyAchieversWithRanks();

        $this->assertCount(2, $result);
        $this->assertSame([1, 5, 2], [$result[0]['rank'], $result[0]['weekly_days'], $result[0]['anti_habits']->count()]);
        $this->assertSame([3, 3, 2], [$result[1]['rank'], $result[1]['weekly_days'], $result[1]['anti_habits']->count()]);
    }

    #[Test]
    public function それ以外は上位3グループ(): void
    {
        $this->travelToLocal('2026-10-09 12:00:00');
        $this->achiever(5);
        $this->achiever(3);
        $this->achiever(1);

        $result = AntiHabit::topWeeklyAchieversWithRanks();

        $this->assertSame([[1, 5], [2, 3], [3, 1]], array_map(fn ($r) => [$r['rank'], $r['weekly_days']], $result));
    }

    #[Test]
    public function 同じ日数の場合は作成日時順(): void
    {
        $a2 = $this->achiever(2, '2026-01-02 00:00:00');
        $a1 = $this->achiever(2, '2026-01-01 00:00:00');
        $a3 = $this->achiever(2, '2026-01-03 00:00:00');

        $result = AntiHabit::topWeeklyAchieversWithRanks();

        $this->assertSame([$a1->id, $a2->id, $a3->id], $result[0]['anti_habits']->pluck('id')->all());
    }

    // ---- 削除 ----

    #[Test]
    public function 削除すると関連データも削除される(): void
    {
        $antiHabit = AntiHabit::factory()->create();
        $this->record($antiHabit, 0);
        Reaction::factory()->for($antiHabit)->create();
        Comment::factory()->for($antiHabit)->create();
        Bookmark::factory()->for($antiHabit)->create();
        NotificationSetting::factory()->for($antiHabit)->create();
        $antiHabit->syncTagNames('タグ');

        $antiHabit->delete();

        $this->assertSame(0, AntiHabit::count());
        $this->assertSame(0, AntiHabitRecord::count());
        $this->assertSame(0, Reaction::count());
        $this->assertSame(0, Comment::count());
        $this->assertSame(0, Bookmark::count());
        $this->assertSame(0, NotificationSetting::count());
        $this->assertDatabaseCount('anti_habit_tags', 0);
        $this->assertSame(1, Tag::count());
        $this->assertSame(1, User::whereKey($antiHabit->user_id)->count());
    }
}
