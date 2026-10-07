<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Tag extends Model
{
    use HasFactory;

    public const MAX_NAME_LENGTH = 15;

    protected $fillable = ['name'];

    public function antiHabitTags(): HasMany
    {
        return $this->hasMany(AntiHabitTag::class);
    }

    public function antiHabits(): BelongsToMany
    {
        return $this->belongsToMany(AntiHabit::class, 'anti_habit_tags')->withTimestamps();
    }

    /**
     * names の Tag を DB から探し、なければ作成して返す。
     *
     * @param  array<int, string>  $names
     * @return Collection<int, Tag>
     */
    public static function findOrCreateByNames(array $names): Collection
    {
        return collect($names)
            ->map(fn ($name) => trim($name))
            ->filter(fn ($name) => $name !== '')
            ->map(fn ($name) => static::firstOrCreate(['name' => $name]))
            ->values();
    }

    protected static function booted(): void
    {
        static::deleting(function (Tag $tag) {
            $tag->antiHabitTags()->delete();
        });
    }
}
