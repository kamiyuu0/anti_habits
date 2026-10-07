<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

abstract class Controller
{
    /** Turbo Stream での応答を受け付けるリクエストか */
    protected function wantsTurboStream(Request $request): bool
    {
        return str_contains((string) $request->header('Accept'), 'text/vnd.turbo-stream.html');
    }

    /** Turbo Stream 形式のレスポンスを返す */
    protected function turboStream(string $view, array $data = [], int $status = 200): Response
    {
        return response()
            ->view($view, $data, $status)
            ->header('Content-Type', 'text/vnd.turbo-stream.html; charset=utf-8');
    }
}
