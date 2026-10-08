<?php

use App\Jobs\NotifyDispatcherJob;
use Illuminate\Support\Facades\Schedule;

// Rails 版の config/recurring.yml (notify_dispatcher: */5 * * * *) 相当
Schedule::job(new NotifyDispatcherJob)->everyFiveMinutes()->withoutOverlapping();
