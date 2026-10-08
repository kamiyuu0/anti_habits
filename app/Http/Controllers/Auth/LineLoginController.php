<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * LINE ログイン / ログイン済みユーザーへの LINE アカウント連携
 * (Rails 版の Users::OmniauthCallbacksController 相当)
 */
class LineLoginController extends Controller
{
    private const PROVIDER = 'line';

    public function redirect()
    {
        return $this->driver()->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $lineUser = $this->driver()->user();
        } catch (Throwable $e) {
            Log::warning('LINE login failed', ['exception' => $e]);
            $lineUser = null;
        }

        return Auth::check()
            ? $this->linkLineAccount($request, $lineUser)
            : $this->login($request, $lineUser);
    }

    private function login(Request $request, ?SocialiteUser $lineUser)
    {
        if (! $lineUser || ! $lineUser->getId()) {
            return redirect()->route('login')->with('alert', 'LINE アカウントによる認証に失敗しました。');
        }

        $user = User::firstOrNew(['provider' => self::PROVIDER, 'uid' => $lineUser->getId()]);

        if (! $user->exists) {
            $user->email = $lineUser->getEmail() ?: "{$lineUser->getId()}-".self::PROVIDER.'@example.com';
            $user->name = (string) $lineUser->getName();
            $user->setPassword(Str::random(20));
            $user->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('anti_habits.index')->with('notice', 'ログインしました');
    }

    private function linkLineAccount(Request $request, ?SocialiteUser $lineUser)
    {
        $currentUser = $request->user();

        if (! $lineUser || ! $lineUser->getId()) {
            return redirect()->route('users.show', $currentUser)->with('alert', 'LINE連携に失敗しました');
        }

        if (User::where('provider', self::PROVIDER)->where('uid', $lineUser->getId())->exists()) {
            return redirect()->route('users.show', $currentUser)->with('alert', 'このLINEアカウントは既に登録されています');
        }

        $currentUser->update(['provider' => self::PROVIDER, 'uid' => $lineUser->getId()]);

        return redirect()->route('users.show', $currentUser)->with('notice', 'LINEアカウントを紐付けました');
    }

    private function driver()
    {
        // Rails 版 (omniauth-line) と同じスコープ。email スコープは LINE 側の申請が必要なため要求しない
        return Socialite::driver(self::PROVIDER)->setScopes(['profile', 'openid']);
    }
}
