<?php

namespace App\Http\Controllers;

use App\Enums\ReactionKind;
use App\Models\AntiHabit;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function store(Request $request, AntiHabit $antiHabit)
    {
        $kind = $this->authorizeAndResolveKind($request, $antiHabit);

        $request->user()->reaction($antiHabit, $kind);

        return $this->respond($request, $antiHabit, $kind);
    }

    public function destroy(Request $request, AntiHabit $antiHabit)
    {
        $kind = $this->authorizeAndResolveKind($request, $antiHabit);

        $request->user()->unreaction($antiHabit, $kind);

        return $this->respond($request, $antiHabit, $kind);
    }

    private function authorizeAndResolveKind(Request $request, AntiHabit $antiHabit): ReactionKind
    {
        abort_unless($antiHabit->is_public || $request->user()->own($antiHabit), 403);

        $kind = ReactionKind::fromName($request->input('reaction_kind'));
        abort_if($kind === null, 422);

        return $kind;
    }

    private function respond(Request $request, AntiHabit $antiHabit, ReactionKind $kind)
    {
        if (! $this->wantsTurboStream($request)) {
            return back(303);
        }

        return $this->turboStream('reactions.update', ['antiHabit' => $antiHabit, 'kind' => $kind]);
    }
}
