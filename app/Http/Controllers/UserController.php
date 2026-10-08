<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    /** マイページ (Rails 版と同様、URL の ID に関わらずログインユーザー自身を表示する) */
    public function show(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('anti_habits.index');
        }

        return view('users.show', [
            'user' => $user,
            'antiHabits' => $user->antiHabits()->with(['tags', 'reactions', 'comments', 'user'])->recent()->get(),
        ]);
    }
}
