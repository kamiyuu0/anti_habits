<?php

namespace App\Http\Controllers;

use App\Http\Requests\AntiHabitRequest;
use App\Models\AntiHabit;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AntiHabitController extends Controller
{
    public const PER_PAGE = 10;

    public function index(Request $request)
    {
        // 検索パラメータは Rails (ransack) 版と同じ q[title_or_description_cont] / q[tags_name_in]
        $keyword = trim((string) $request->input('q.title_or_description_cont'));
        $tagName = (string) $request->input('q.tags_name_in');

        $antiHabits = AntiHabit::query()
            ->publiclyVisible()
            ->when($keyword !== '', function (Builder $query) use ($keyword) {
                $like = '%'.self::escapeLike($keyword).'%';
                $query->where(fn (Builder $q) => $q
                    ->where('anti_habits.title', 'ilike', $like)
                    ->orWhere('anti_habits.description', 'ilike', $like));
            })
            ->when($tagName !== '', fn (Builder $query) => $query->taggedWith($tagName))
            ->withAssociations()
            ->recent()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('anti_habits.index', [
            'antiHabits' => $antiHabits,
            'allTags' => Tag::orderBy('name')->get(),
            'topWeeklyAchievers' => AntiHabit::topWeeklyAchieversWithRanks(),
            'searchQuery' => $request->input('q'),
        ]);
    }

    public function autocomplete(Request $request)
    {
        $query = trim((string) $request->input('q'));

        $antiHabits = $query === ''
            ? collect()
            : AntiHabit::query()
                ->publiclyVisible()
                ->where('title', 'ilike', '%'.self::escapeLike($query).'%')
                ->recent()
                ->limit(10)
                ->get();

        return view('anti_habits._autocomplete_result', ['antiHabits' => $antiHabits]);
    }

    public function create()
    {
        return view('anti_habits.create', ['antiHabit' => new AntiHabit]);
    }

    public function store(AntiHabitRequest $request)
    {
        $antiHabit = DB::transaction(function () use ($request) {
            $antiHabit = $request->user()->antiHabits()->create($request->safe()->except('tag_names'));
            $antiHabit->syncTagNames($request->input('tag_names'));

            return $antiHabit;
        });

        return redirect()->route('anti_habits.show', $antiHabit)->with('notice', '悪習慣を登録しました。');
    }

    public function show(Request $request, string $id)
    {
        $antiHabit = AntiHabit::with(['tags', 'user'])->findOrFail($id);
        $user = $request->user();
        $isOwner = $user?->own($antiHabit) ?? false;

        if (! $antiHabit->is_public && ! $isOwner) {
            return redirect()->route('anti_habits.index')->with('alert', 'このページにアクセスする権限がありません。');
        }

        $fontSize = match (true) {
            mb_strlen($antiHabit->title) > 15 => '25',
            mb_strlen($antiHabit->title) > 10 => '30',
            default => '50',
        };

        return view('anti_habits.show', [
            'antiHabit' => $antiHabit,
            'isOwner' => $isOwner,
            'todayRecord' => $isOwner ? $antiHabit->todayRecord() : null,
            'comments' => $antiHabit->comments()->with('user')->latest()->get(),
            'calendarData' => $isOwner ? $antiHabit->calendarData(90) : null,
            'ogpImageUrl' => "https://res.cloudinary.com/antihabits/image/upload/l_text:Sawarabi%20Gothic_{$fontSize}_solid:{$antiHabit->title},co_rgb:333,w_500,c_fit/v1757602327/anti_habits_dynamic_ogp_zyyjyk.png",
        ]);
    }

    public function edit(Request $request, string $id)
    {
        $antiHabit = $request->user()->antiHabits()->with('tags')->findOrFail($id);

        return view('anti_habits.edit', ['antiHabit' => $antiHabit]);
    }

    public function update(AntiHabitRequest $request, string $id)
    {
        $antiHabit = $request->user()->antiHabits()->findOrFail($id);

        DB::transaction(function () use ($request, $antiHabit) {
            $antiHabit->update($request->safe()->except('tag_names'));
            $antiHabit->syncTagNames($request->input('tag_names'));
        });

        return redirect()->route('anti_habits.show', $antiHabit)->with('notice', '悪習慣を更新しました。');
    }

    public function destroy(Request $request, string $id)
    {
        $antiHabit = $request->user()->antiHabits()->findOrFail($id);

        DB::transaction(fn () => $antiHabit->delete());

        return redirect()->route('anti_habits.index', status: 303)->with('notice', '悪習慣を削除しました。');
    }

    private static function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
