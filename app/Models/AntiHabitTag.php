<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AntiHabitTag extends Model
{
    protected $fillable = ['anti_habit_id', 'tag_id'];

    public function antiHabit(): BelongsTo
    {
        return $this->belongsTo(AntiHabit::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
