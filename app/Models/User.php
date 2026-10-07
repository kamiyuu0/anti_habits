<?php

namespace App\Models;

use App\Enums\ReactionKind;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

/**
 * Devise と同じ users テーブルを使うユーザーモデル。
 * - パスワードは encrypted_password カラム (bcrypt) に保存する
 * - remember_token カラムが無いため、remember_created_at を元にトークンを導出する
 */
class User extends Model implements AuthenticatableContract
{
    use Authenticatable, HasFactory, Notifiable;

    public const MAX_NAME_LENGTH = 10;

    public const MIN_PASSWORD_LENGTH = 6;

    public const MAX_PASSWORD_LENGTH = 128;

    protected $fillable = ['name', 'email', 'provider', 'uid'];

    protected $hidden = ['encrypted_password', 'reset_password_token'];

    protected $attributes = [
        'email' => '',
        'encrypted_password' => '',
        'name' => '',
    ];

    protected function casts(): array
    {
        return [
            'reset_password_sent_at' => 'datetime',
            'remember_created_at' => 'datetime',
        ];
    }

    // ---- 認証 ----

    public function getAuthPasswordName(): string
    {
        return 'encrypted_password';
    }

    public function setPassword(string $plain): void
    {
        $this->encrypted_password = bcrypt($plain);
    }

    public function getRememberTokenName(): string
    {
        return 'remember_created_at';
    }

    /**
     * Devise と同様に remember_created_at とパスワードハッシュから remember トークンを導出する。
     * パスワード変更やログアウト (remember_created_at の更新) で無効になる。
     */
    public function getRememberToken(): ?string
    {
        if ($this->remember_created_at === null) {
            return null;
        }

        return hash_hmac(
            'sha256',
            $this->getKey().'|'.$this->remember_created_at->format('U.u').'|'.$this->encrypted_password,
            config('app.key')
        );
    }

    public function setRememberToken($value): void
    {
        // Laravel はログアウト時にランダム値で呼び出す。値自体は保存できないので作成時刻を更新して旧トークンを無効化する。
        $this->remember_created_at = $value === null ? null : now();
    }

    // ---- リレーション ----

    public function antiHabits(): HasMany
    {
        return $this->hasMany(AntiHabit::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function bookmarkedAntiHabits(): BelongsToMany
    {
        return $this->belongsToMany(AntiHabit::class, 'bookmarks')->withTimestamps();
    }

    protected static function booted(): void
    {
        // Rails の dependent: :destroy 相当
        static::deleting(function (User $user) {
            $user->antiHabits()->get()->each->delete();
            $user->reactions()->delete();
            $user->comments()->get()->each->delete();
            $user->bookmarks()->delete();
        });
    }

    // ---- ドメインロジック ----

    public function own(?Model $object): bool
    {
        return $object !== null && (int) $this->getKey() === (int) $object->user_id;
    }

    public function isLineLinked(): bool
    {
        return $this->provider === 'line';
    }

    public function reaction(AntiHabit $antiHabit, ReactionKind $kind): Reaction
    {
        return $this->reactions()->firstOrCreate([
            'anti_habit_id' => $antiHabit->id,
            'reaction_kind' => $kind,
        ]);
    }

    public function unreaction(AntiHabit $antiHabit, ReactionKind $kind): void
    {
        $this->reactions()
            ->where('anti_habit_id', $antiHabit->id)
            ->where('reaction_kind', $kind->value)
            ->first()
            ?->delete();
    }

    public function hasReacted(AntiHabit $antiHabit, ReactionKind $kind): bool
    {
        return $this->reactions()
            ->where('anti_habit_id', $antiHabit->id)
            ->where('reaction_kind', $kind->value)
            ->exists();
    }

    public function bookmark(AntiHabit $antiHabit): Bookmark
    {
        return $this->bookmarks()->firstOrCreate(['anti_habit_id' => $antiHabit->id]);
    }

    public function unbookmark(AntiHabit $antiHabit): void
    {
        $this->bookmarks()->where('anti_habit_id', $antiHabit->id)->first()?->delete();
    }

    public function hasBookmarked(AntiHabit $antiHabit): bool
    {
        return $this->bookmarks()->where('anti_habit_id', $antiHabit->id)->exists();
    }

    public function canBookmark(AntiHabit $antiHabit): bool
    {
        return $antiHabit->is_public && ! $this->own($antiHabit);
    }
}
