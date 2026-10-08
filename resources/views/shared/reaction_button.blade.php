{{-- 1 種類分のリアクションボタン (Turbo Stream で差し替え) --}}
@php($reacted = auth()->user()->hasReacted($antiHabit, $kind))
<form action="{{ route('anti_habits.reactions.'.($reacted ? 'destroy' : 'store'), $antiHabit) }}"
      method="post"
      id="reaction-{{ $kind->key() }}-form-for-anti_habit-{{ $antiHabit->id }}"
      class="inline"
      data-turbo-stream="true">
  @csrf
  @if ($reacted)
    @method('DELETE')
  @endif
  <input type="hidden" name="reaction_kind" value="{{ $kind->key() }}">
  <button type="submit" class="badge {{ $reacted ? 'badge-outline badge-primary' : 'badge-dash badge-base' }}">
    {{ $kind->emoji() }}
    {{ $antiHabit->reactionCount($kind) }}
  </button>
</form>
