@extends('layouts.app')

@section('content')
@php($hasQuery = filled($searchQuery))
<div class="container mx-auto px-4 py-6">
  <div class="mb-8">
    <h1 class="text-4xl font-bold text-center mb-4">悪習慣一覧</h1>
    <p class="text-center text-base-content/70">みんなが克服したい悪習慣を見てみましょう</p>
  </div>

  <!-- 検索フォーム -->
  <div class="mb-6">
    <div class="card bg-base-100 shadow-lg">
      <div class="card-body">
        <form action="{{ route('anti_habits.index') }}" method="get" class="space-y-4">
          <div class="relative"
               data-controller="autocomplete"
               data-autocomplete-url-value="{{ route('anti_habits.autocomplete') }}"
               data-autocomplete-min-length-value="1"
               role="combobox">
            <div class="join w-full">
              <input type="search" name="q[title_or_description_cont]"
                  placeholder="タイトルか説明文で検索..."
                  class="input input-bordered join-item w-full"
                  value="{{ request('q.title_or_description_cont') }}"
                  data-autocomplete-target="input"
                  data-action="input->autocomplete#onInput keydown->autocomplete#onKeydown blur->autocomplete#onBlur"
                  autocomplete="off">
              <input type="submit" value="検索" class="btn btn-primary join-item">
            </div>
            <ul class="list-group absolute z-10 w-full top-full mt-1 max-h-60 overflow-y-auto bg-base-100 shadow-lg rounded-lg border border-base-300"
                data-autocomplete-target="results"
                data-action="click->autocomplete#selectOption"
                hidden></ul>
          </div>
          <div class="mt-4">
            <label for="q_tags_name_in" class="label-text mb-2 block">タグで絞り込み</label>
            <select name="q[tags_name_in]" id="q_tags_name_in" class="select select-bordered w-full">
              <option value="">指定なし</option>
              @foreach ($allTags as $tag)
                <option value="{{ $tag->name }}" @selected(request('q.tags_name_in') === $tag->name)>{{ $tag->name }}</option>
              @endforeach
            </select>
          </div>
          @if ($hasQuery)
            <div class="mt-2 text-center">
              <a href="{{ route('anti_habits.index') }}" class="btn btn-sm btn-ghost">検索をクリア</a>
            </div>
          @endif
        </form>
      </div>
    </div>
  </div>

  <!-- 週間達成ランキング -->
  @if (! empty($topWeeklyAchievers))
    <div class="mb-8">
      <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
          <h2 class="card-title text-2xl mb-4">
            🏆 週間達成ランキング TOP3
          </h2>
          <div class="space-y-4">
            @foreach ($topWeeklyAchievers as $rankData)
              @foreach ($rankData['anti_habits'] as $antiHabit)
                <div class="flex items-center gap-4 p-4 bg-base-200 rounded-lg hover:bg-base-300 transition-colors">
                  <!-- ランキング順位 -->
                  <div class="flex-shrink-0">
                    @switch($rankData['rank'])
                      @case(1)
                        <span class="text-4xl">🥇</span>
                        @break
                      @case(2)
                        <span class="text-4xl">🥈</span>
                        @break
                      @case(3)
                        <span class="text-4xl">🥉</span>
                        @break
                    @endswitch
                  </div>

                  <!-- 悪習慣情報 -->
                  <div class="flex-grow">
                    <a href="{{ route('anti_habits.show', $antiHabit) }}" class="hover:underline">
                      <h3 class="font-bold text-lg">{{ $antiHabit->title }}</h3>
                    </a>
                    <p class="text-sm text-base-content/70">
                      {{ $antiHabit->user->name }}さん
                    </p>
                  </div>

                  <!-- 週間達成日数 -->
                  <div class="flex-shrink-0 text-right">
                    <div class="stat-value text-3xl text-primary">
                      {{ $rankData['weekly_days'] }}
                    </div>
                    <div class="stat-desc">日達成 / 今週</div>
                  </div>
                </div>
              @endforeach
            @endforeach
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- 悪習慣一覧 -->
  <div class="space-y-6">
    @forelse ($antiHabits as $antiHabit)
      @include('anti_habits._card', ['antiHabit' => $antiHabit])
    @empty
      <!-- 空の状態 -->
      <div class="hero min-h-96">
        <div class="hero-content text-center">
          <div class="max-w-md">
            <div class="mb-6">
              <svg class="mx-auto w-24 h-24 text-base-content/30" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
            </div>
            <h3 class="text-2xl font-bold mb-4">
              {{ $hasQuery ? '検索結果が見つかりませんでした' : 'まだ悪習慣が投稿されていません' }}
            </h3>
            <p class="mb-6">
              {{ $hasQuery ? '検索条件を変更して再度お試しください' : '最初の悪習慣を登録して、目標達成への第一歩を踏み出しましょう' }}
            </p>
          </div>
        </div>
      </div>
    @endforelse
  </div>

  <!-- ページネーション -->
  {{ $antiHabits->onEachSide(2)->links() }}
</div>
@endsection
