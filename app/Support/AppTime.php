<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * DB の日時は Rails 版と同じく UTC で保存し、「今日」などの判定は表示用タイムゾーン (Asia/Tokyo) で行う。
 */
class AppTime
{
    public static function zone(): string
    {
        return config('app.display_timezone', 'Asia/Tokyo');
    }

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::zone());
    }

    public static function today(): CarbonImmutable
    {
        return self::now()->startOfDay();
    }

    public static function yesterday(): CarbonImmutable
    {
        return self::today()->subDay();
    }
}
