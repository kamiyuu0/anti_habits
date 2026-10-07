<?php

namespace App\Http\Controllers;

use App\Models\AntiHabit;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $antiHabits = $request->user()
            ->bookmarkedAntiHabits()
            ->publiclyVisible()
            ->withAssociations()
            ->orderByDesc('bookmarks.created_at')
            ->paginate(AntiHabitController::PER_PAGE);

        return view('bookmarks.index', ['antiHabits' => $antiHabits]);
    }

    public function store(Request $request, AntiHabit $antiHabit)
    {
        abort_unless($request->user()->canBookmark($antiHabit), 403);

        $request->user()->bookmark($antiHabit);

        return $this->respond($request, $antiHabit);
    }

    public function destroy(Request $request, AntiHabit $antiHabit)
    {
        $request->user()->unbookmark($antiHabit);

        return $this->respond($request, $antiHabit);
    }

    private function respond(Request $request, AntiHabit $antiHabit)
    {
        if (! $this->wantsTurboStream($request)) {
            return back(303);
        }

        return $this->turboStream('bookmarks.update', ['antiHabit' => $antiHabit]);
    }
}
