<?php

namespace App\Models;

use App\Enums\ReactionKind;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaction extends Model
{
    use HasFactory;

    protected $fillable = ['anti_habit_id', 'reaction_kind'];

    protected function casts(): array
    {
        return [
            'reaction_kind' => ReactionKind::class,
        ];
    }

    public function antiHabit(): BelongsTo
    {
        return $this->belongsTo(AntiHabit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
