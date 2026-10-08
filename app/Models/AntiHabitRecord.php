<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AntiHabitRecord extends Model
{
    use HasFactory;

    protected $fillable = ['recorded_on'];

    protected function casts(): array
    {
        return [
            'recorded_on' => 'date:Y-m-d',
        ];
    }

    public function antiHabit(): BelongsTo
    {
        return $this->belongsTo(AntiHabit::class);
    }

    protected static function booted(): void
    {
        // 記録の作成・削除で親の目標達成フラグを更新する
        $check = fn (AntiHabitRecord $record) => $record->antiHabit?->checkAndUpdateGoalAchievement();

        static::created($check);
        static::deleted($check);
    }
}
