<?php

namespace App\Http\Controllers;

use App\Models\AntiHabit;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function store(Request $request, AntiHabit $antiHabit)
    {
        abort_unless($antiHabit->is_public || $request->user()->own($antiHabit), 403);

        $validator = Validator::make($request->only('body'), [
            'body' => ['required', 'string', 'max:'.Comment::MAX_BODY_LENGTH],
        ]);

        if ($validator->fails()) {
            if (! $this->wantsTurboStream($request)) {
                return back()->withErrors($validator)->withInput();
            }

            return $this->turboStream('comments.store_failed', [
                'antiHabit' => $antiHabit,
                'errors' => $validator->errors(),
                'body' => $request->input('body'),
            ]);
        }

        $comment = $antiHabit->comments()->make($validator->validated());
        $comment->user()->associate($request->user());
        $comment->save();

        if (! $this->wantsTurboStream($request)) {
            return redirect()->route('anti_habits.show', $antiHabit);
        }

        return $this->turboStream('comments.store', [
            'antiHabit' => $antiHabit->refresh(),
            'comment' => $comment->load('user'),
        ]);
    }
}
