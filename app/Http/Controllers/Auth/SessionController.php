<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = [
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'password' => (string) $request->input('password'),
        ];

        if ($credentials['email'] === '' || $credentials['password'] === '' || ! Auth::attempt($credentials, $request->boolean('remember_me'))) {
            return back()
                ->withInput($request->only('email', 'remember_me'))
                ->with('alert', 'メールアドレスまたはパスワードが正しくありません。');
        }

        $request->session()->regenerate();

        return redirect()->route('users.show', Auth::id())->with('notice', 'ログインしました。');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('root', status: 303)->with('notice', 'ログアウトしました。');
    }
}
