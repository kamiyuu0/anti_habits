<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * LINE Messaging API のプッシュメッセージ送信クライアント
 */
class LineMessagingClient
{
    private const PUSH_ENDPOINT = 'https://api.line.me/v2/bot/message/push';

    public function pushText(string $to, string $text): Response
    {
        return Http::withToken((string) config('services.line_messaging.channel_token'))
            ->acceptJson()
            ->post(self::PUSH_ENDPOINT, [
                'to' => $to,
                'messages' => [['type' => 'text', 'text' => $text]],
            ]);
    }
}
