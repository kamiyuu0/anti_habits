<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:'.User::MAX_NAME_LENGTH, Rule::unique('users', 'name')],
            'email' => ['required', 'string', 'regex:/\A[^@\s]+@[^@\s]+\z/', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:'.User::MIN_PASSWORD_LENGTH, 'max:'.User::MAX_PASSWORD_LENGTH, 'confirmed'],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            // 登録済みのメールアドレスであることを推測されないよう、Rails 版と同じく汎用メッセージのみを返す
            if (isset($validator->failed()['email']['Unique'])) {
                $errors = new MessageBag(['base' => '登録できませんでした。']);
            }

            return back()->withErrors($errors)->withInput($request->except('password', 'password_confirmation'));
        }

        $user = new User($request->only('name', 'email'));
        $user->setPassword($request->input('password'));
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('anti_habits.index')->with('notice', 'ご登録ありがとうございます。正常にサインアップしました。');
    }

    public function edit()
    {
        return view('auth.edit');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:'.User::MAX_NAME_LENGTH, Rule::unique('users', 'name')->ignore($user->id)],
        ]);

        $user->update($validated);

        return redirect()->route('users.show', $user)->with('notice', 'アカウント情報を更新しました。');
    }
}
