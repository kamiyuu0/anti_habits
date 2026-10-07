{{-- ブックマーク / ブックマーク解除ボタン (Turbo Stream で差し替え) --}}
@php($bookmarked = auth()->user()->hasBookmarked($antiHabit))
<form action="{{ route('anti_habits.bookmarks.'.($bookmarked ? 'destroy' : 'store'), $antiHabit) }}"
      method="post"
      id="bookmark-button-for-anti_habit-{{ $antiHabit->id }}"
      class="inline"
      data-turbo-stream="true">
  @csrf
  @if ($bookmarked)
    @method('DELETE')
    <button type="submit" class="btn btn-primary btn-sm">
      <i class="fas fa-bookmark"></i>
    </button>
  @else
    <button type="submit" class="btn btn-ghost btn-sm">
      <i class="far fa-bookmark"></i>
    </button>
  @endif
</form>
