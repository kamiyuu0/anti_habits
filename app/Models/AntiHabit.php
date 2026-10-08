<?php

namespace App\Models;

use App\Enums\ReactionKind;
use App\Support\AppTime;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AntiHabit extends Model
{
    use HasFactory;

    public const MAX_TITLE_LENGTH = 20;

    public const MAX_DESCRIPTION_LENGTH = 80;

    protected $fillable = ['title', 'description', 'is_public', 'goal_days'];

    protected $attributes = [
        'is_public' => true,
        'goal_achieved' => false,
        'comments_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'goal_achieved' => 'boolean',
            'goal_days' => 'integer',
            'comments_count' => 'integer',
        ];
    }

    // ---- リレーション ----

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function antiHabitRecords(): HasMany
    {
        return $this->hasMany(AntiHabitRecord::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function antiHabitTags(): HasMany
    {
        return $this->hasMany(AntiHabitTag::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'anti_habit_tags')->withTimestamps();
    }

    public function notificationSetting(): HasOne
    {
        return $this->hasOne(NotificationSetting::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    // ---- スコープ ----

    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('anti_habits.is_public', true);
    }

    public function scopeRecent(Builder $query): void
    {
        $query->orderByDesc('anti_habits.created_at');
    }

    public function scopeWithAssociations(Builder $query): void
    {
        $query->with(['user', 'tags', 'reactions', 'comments']);
    }

    public function scopeTaggedWith(Builder $query, string|array $names): void
    {
        $query->whereHas('tags', fn (Builder $q) => $q->whereIn('tags.name', (array) $names));
    }

    // ---- ライフサイクル ----

    protected static function booted(): void
    {
        static::saved(function (AntiHabit $antiHabit) {
            $antiHabit->checkAndUpdateGoalAchievement();
        });

        // Rails の dependent: :destroy 相当
        static::deleting(function (AntiHabit $antiHabit) {
            $antiHabit->antiHabitRecords()->delete();
            $antiHabit->reactions()->delete();
            $antiHabit->comments()->delete();
            $antiHabit->antiHabitTags()->delete();
            $antiHabit->notificationSetting()->delete();
            $antiHabit->bookmarks()->delete();
        });
    }

    // ---- ランキング ----

    /**
     * 今週 (月曜始まり) の達成日数ランキング TOP3 を返す。
     *
     * @return array<int, array{rank: int, weekly_days: int, anti_habits: Collection<int, AntiHabit>}>
     */
    public static function topWeeklyAchieversWithRanks(): array
    {
        $today = AppTime::today();
        $startOfWeek = $today->startOfWeek(CarbonInterface::MONDAY);
        $endOfWeek = $today->min($startOfWeek->addDays(6));

        $weeklyCounts = DB::table('anti_habit_records')
            ->select('anti_habit_id', DB::raw('COUNT(*) AS count'))
            ->whereBetween('recorded_on', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->groupBy('anti_habit_id');

        $candidates = static::query()
            ->publiclyVisible()
            ->with('user')
            ->joinSub($weeklyCounts, 'weekly', 'weekly.anti_habit_id', '=', 'anti_habits.id')
            ->select('anti_habits.*', 'weekly.count AS weekly_days_count')
            ->orderByDesc('weekly.count')
            ->get();

        $grouped = $candidates
            ->groupBy(fn (AntiHabit $a) => (int) $a->weekly_days_count)
            ->sortKeysDesc();

        $rankedData = [];
        $currentRank = 1;
        foreach ($grouped as $days => $antiHabits) {
            $rankedData[] = [
                'rank' => $currentRank,
                'weekly_days' => (int) $days,
                'anti_habits' => $antiHabits->sortBy('created_at')->values(),
            ];
            $currentRank += $antiHabits->count();
        }

        $firstPlaceCount = isset($rankedData[0]) ? $rankedData[0]['anti_habits']->count() : 0;
        if ($firstPlaceCount >= 3) {
            return array_slice($rankedData, 0, 1);
        }

        $firstTwoCount = collect(array_slice($rankedData, 0, 2))->sum(fn ($d) => $d['anti_habits']->count());
        if ($firstTwoCount >= 3) {
            return array_slice($rankedData, 0, 2);
        }

        return array_slice($rankedData, 0, 3);
    }

    // ---- 記録・目標 ----

    public function todayRecord(): ?AntiHabitRecord
    {
        return $this->antiHabitRecords()->whereDate('recorded_on', AppTime::today()->toDateString())->first();
    }

    /** 今日 (未記録なら昨日) から遡った連続記録日数 */
    public function consecutiveDaysAchieved(): int
    {
        $startDate = $this->todayRecord() ? AppTime::today() : AppTime::yesterday();

        $records = $this->antiHabitRecords()
            ->where('recorded_on', '<=', $startDate->toDateString())
            ->orderByDesc('recorded_on')
            ->pluck('recorded_on');

        $count = 0;
        $expectedDate = $startDate;

        foreach ($records as $recordedOn) {
            if ($recordedOn->toDateString() !== $expectedDate->toDateString()) {
                break;
            }
            $count++;
            $expectedDate = $expectedDate->subDay();
        }

        return $count;
    }

    public function goalReached(): bool
    {
        if ($this->goal_days === null) {
            return false;
        }

        return $this->consecutiveDaysAchieved() >= $this->goal_days;
    }

    /** 目標日数・連続記録に応じて goal_achieved を更新する */
    public function checkAndUpdateGoalAchievement(): void
    {
        if ($this->goal_days === null) {
            // 目標日数が外された場合は達成フラグをリセット
            if ($this->wasChanged('goal_days') && $this->goal_achieved) {
                $this->updateColumn('goal_achieved', false);
            }

            return;
        }

        $reached = $this->goalReached();
        if ($this->goal_achieved !== $reached) {
            $this->updateColumn('goal_achieved', $reached);
        }
    }

    /**
     * ヒートマップ用のカレンダーデータ ([["Y-m-d", 0|1], ...])
     *
     * @return array<int, array{0: string, 1: int}>
     */
    public function calendarData(int $days = 90): array
    {
        $endDate = AppTime::today();
        $startDate = $endDate->subDays($days - 1);

        $recordedDates = $this->antiHabitRecords()
            ->whereBetween('recorded_on', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('recorded_on')
            ->map(fn ($date) => $date->toDateString())
            ->flip();

        $data = [];
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $key = $date->toDateString();
            $data[] = [$key, $recordedDates->has($key) ? 1 : 0];
        }

        return $data;
    }

    // ---- タグ ----

    public function tagNamesAsString(): string
    {
        return $this->tags->pluck('name')->implode(', ');
    }

    /**
     * "a, b, c" 形式の文字列からタグを分解する
     *
     * @return array<int, string>
     */
    public static function parseTagNames(?string $tagNames): array
    {
        return collect(explode(',', (string) $tagNames))
            ->map(fn ($name) => trim($name))
            ->filter(fn ($name) => $name !== '')
            ->values()
            ->all();
    }

    public function syncTagNames(?string $tagNames): void
    {
        $tags = Tag::findOrCreateByNames(self::parseTagNames($tagNames));

        $this->tags()->sync($tags->pluck('id')->all());
        $this->unsetRelation('tags');
    }

    // ---- リアクション ----

    public function reactionCount(ReactionKind $kind): int
    {
        if ($this->relationLoaded('reactions')) {
            return $this->reactions->where('reaction_kind', $kind)->count();
        }

        return $this->reactions()->where('reaction_kind', $kind->value)->count();
    }
}
