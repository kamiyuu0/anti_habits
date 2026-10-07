<?php

namespace App\Models;

use App\Support\AppTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * notification_time は Rails 版と同じく UTC の時刻で保存する。
 * (Rails の time 型はタイムゾーン変換されて保存されていたため)
 */
class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_enabled',
        'notify_on_reaction',
        'notify_on_comment',
    ];

    protected $attributes = [
        'notification_enabled' => false,
        'notify_on_reaction' => false,
        'notify_on_comment' => false,
    ];

    protected function casts(): array
    {
        return [
            'notification_enabled' => 'boolean',
            'notify_on_reaction' => 'boolean',
            'notify_on_comment' => 'boolean',
        ];
    }

    public function antiHabit(): BelongsTo
    {
        return $this->belongsTo(AntiHabit::class);
    }

    /** 表示用タイムゾーンの "H:i" で通知時刻を設定する */
    public function setLocalNotificationTime(string $time): void
    {
        $this->notification_time = CarbonImmutable::createFromFormat('Y-m-d H:i', "2000-01-01 {$time}", AppTime::zone())
            ->utc()
            ->format('H:i:s');
    }

    /** 表示用タイムゾーンの "H:i" で通知時刻を返す */
    public function localNotificationTime(): ?string
    {
        if ($this->notification_time === null) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d H:i:s', '2000-01-01 '.substr($this->notification_time, 0, 8), 'UTC')
            ->setTimezone(AppTime::zone())
            ->format('H:i');
    }
}
