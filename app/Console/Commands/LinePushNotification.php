<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LineMessagingClient;
use Illuminate\Console\Command;

/** Rails 版の rake line_pushnotification:send 相当 */
class LinePushNotification extends Command
{
    protected $signature = 'line:push-notification';

    protected $description = 'LINE連携済みの全ユーザーにLINE通知を送ります';

    public function handle(LineMessagingClient $client): int
    {
        User::whereNotNull('uid')->where('uid', '!=', '')->each(function (User $user) use ($client) {
            $response = $client->pushText($user->uid, "今日の悪習慣進捗を登録しましょう \n ".config('app.url').'/');
            $this->line("user #{$user->id}: {$response->status()}");
        });

        return self::SUCCESS;
    }
}
