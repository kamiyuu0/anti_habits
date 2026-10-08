<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Comment extends Model
{
    use HasFactory;

    public const MAX_BODY_LENGTH = 500;

    protected $fillable = ['body'];

    public function antiHabit(): BelongsTo
    {
        return $this->belongsTo(AntiHabit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        // Rails の counter_cache 相当 (anti_habits.comments_count)
        static::created(function (Comment $comment) {
            DB::table('anti_habits')->where('id', $comment->anti_habit_id)->increment('comments_count');
        });
        static::deleted(function (Comment $comment) {
            DB::table('anti_habits')->where('id', $comment->anti_habit_id)->decrement('comments_count');
        });
    }
}
