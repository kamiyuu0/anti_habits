<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChanged;
use App\Mail\ResetPasswordInstructions;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Devise の recoverable 相当。
 * トークンは users.reset_password_token にダイジェストを保存する (別テーブルは使わない)。
 */
class PasswordResetController extends Controller
{
    /** Devise の reset_password_within と同じ有効期限 */
    private const EXPIRES_IN_HOURS = 6;

    public function create()
    {
        return view('auth.passwords.request');
    }

    public function store(Request $request)
    {
        $email = mb_strtolower(trim((string) $request->input('email')));
        $user = $email === '' ? null : User::where('email', $email)->first();

        if (! $user) {
            return back()->withInput()->withErrors(['email' => 'メールアドレスは見つかりませんでした。']);
        }

        $token = Str::random(20);
        $user->forceFill([
            'reset_password_token' => self::digest($token),
            'reset_password_sent_at' => now(),
        ])->save();

        Mail::to($user->email)->send(new ResetPasswordInstructions($user, $token));

        return redirect()->route('login')->with('notice', '数分以内にパスワード再設定の案内メールが届きます。');
    }

    public function edit(Request $request)
    {
        $token = (string) $request->query('reset_password_token');

        if ($token === '') {
            return redirect()->route('login')->with('alert', 'パスワード再設定用のメールからアクセスしないと、このページにはアクセスできません。メール記載のURLが完全であるか確認してください。');
        }

        return view('auth.passwords.reset', ['token' => $token]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:'.User::MIN_PASSWORD_LENGTH, 'max:'.User::MAX_PASSWORD_LENGTH, 'confirmed'],
        ]);

        $token = (string) $request->input('reset_password_token');
        $user = $token === '' ? null : User::where('reset_password_token', self::digest($token))->first();

        if (! $user) {
            return back()->withErrors(['reset_password_token' => 'パスワードリセット用トークンは不正な値です']);
        }

        if ($user->reset_password_sent_at === null || $user->reset_password_sent_at->lt(now()->subHours(self::EXPIRES_IN_HOURS))) {
            return back()->withErrors(['reset_password_token' => 'パスワードリセット用トークンの有効期限が切れました。新しくリクエストしてください。']);
        }

        $user->setPassword($request->input('password'));
        $user->forceFill([
            'reset_password_token' => null,
            'reset_password_sent_at' => null,
        ])->save();

        Mail::to($user->email)->send(new PasswordChanged($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('root')->with('notice', 'パスワードが正常に変更されました。ログインしました。');
    }

    private static function digest(string $token): string
    {
        return hash_hmac('sha256', $token, 'reset_password_token|'.config('app.key'));
    }
}
