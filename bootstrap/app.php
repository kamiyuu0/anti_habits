<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 未ログイン時はログイン画面へ (Devise の authenticate_user! 相当)
        $middleware->redirectGuestsTo(function (Request $request) {
            $request->session()->flash('alert', '続行するにはログインまたは登録が必要です。');

            return route('login');
        });

        // ログイン済みでログイン・新規登録画面にアクセスした場合
        $middleware->redirectUsersTo(function (Request $request) {
            $request->session()->flash('alert', 'すでにログインしています。');

            return route('users.show', Auth::id());
        });

        // Render などのリバースプロキシ配下で https を正しく判定する
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Rails 版の rescue_from ActiveRecord::RecordNotFound 相当
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $e->getPrevious() instanceof ModelNotFoundException || $request->expectsJson()) {
                return null;
            }

            return redirect()
                ->route(Auth::check() ? 'anti_habits.index' : 'root')
                ->with('alert', '指定されたページが見つかりません。');
        });
    })->create();
