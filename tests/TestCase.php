<?php

namespace Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** 表示用タイムゾーン (Asia/Tokyo) の日時に時間を固定する */
    protected function travelToLocal(string $datetime): void
    {
        $this->travelTo(CarbonImmutable::parse($datetime, config('app.display_timezone')));
    }

    protected function turboStreamHeaders(): array
    {
        return ['Accept' => 'text/vnd.turbo-stream.html, text/html, application/xhtml+xml'];
    }
}
