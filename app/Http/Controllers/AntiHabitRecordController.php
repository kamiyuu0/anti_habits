<?php

namespace App\Http\Controllers;

use App\Models\AntiHabit;
use App\Models\AntiHabitRecord;
use App\Support\AppTime;
use Illuminate\Http\Request;

class AntiHabitRecordController extends Controller
{
    public function store(Request $request)
    {
        $antiHabit = AntiHabit::findOrFail($request->input('anti_habit_id'));

        if (! $request->user()->own($antiHabit)) {
            return redirect()->route('anti_habits.show', $antiHabit)->with('alert', '自分の悪習慣のみ記録できます。');
        }

        if ($antiHabit->todayRecord()) {
            return redirect()->route('anti_habits.show', $antiHabit)->with('alert', '今日はすでに記録済みです。');
        }

        $antiHabit->antiHabitRecords()->create(['recorded_on' => AppTime::today()->toDateString()]);

        return redirect()->route('anti_habits.show', $antiHabit)->with('notice', '記録を作成しました。');
    }

    public function destroy(Request $request, AntiHabitRecord $antiHabitRecord)
    {
        $antiHabit = $antiHabitRecord->antiHabit;

        if (! $request->user()->own($antiHabit)) {
            return redirect()->route('anti_habits.show', $antiHabit)->with('alert', '自分の記録のみ削除できます。');
        }

        $antiHabitRecord->delete();

        return redirect()->route('anti_habits.show', [$antiHabit], 303)->with('notice', '記録を削除しました。');
    }
}
