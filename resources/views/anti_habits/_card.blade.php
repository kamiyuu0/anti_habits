{{-- 悪習慣のカード表示 (一覧・ブックマーク・マイページ共通) --}}
<div class="card bg-base-100 shadow-xl mb-4 hover:shadow-2xl transition-shadow rounded-2xl border border-base-300">
  <div class="card-body relative">
    <!-- ブックマークボタン（右上） -->
    @if (auth()->check() && auth()->user()->canBookmark($antiHabit))
      <div class="absolute top-4 right-4">
        @include('shared.bookmark_button', ['antiHabit' => $antiHabit])
      </div>
    @endif

    <!-- ヘッダー部分：ユーザー名と作成日 -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center">
          <i class="fas fa-user text-sm mr-2"></i>
          <div>
            <span class="font-semibold">{{ $antiHabit->user->name }}</span>
          </div>
      </div>

      <!-- 非公開バッジ（作成者本人のみ表示） -->
      @if (auth()->user()?->own($antiHabit) && ! $antiHabit->is_public)
        <div class="badge badge-warning text-[8px] sm:text-sm">
          非公開
        </div>
      @endif
    </div>

    <!-- タイトル -->
    <div class="mb-3">
      <h3 class="card-title text-lg mb-2">
        <a href="{{ route('anti_habits.show', $antiHabit) }}" class="link link-hover">
          {{ $antiHabit->title }}
        </a>
      </h3>
      <div class="flex justify-start gap-2">
        <div class="badge badge-success badge-lg">
          連続{{ $antiHabit->consecutiveDaysAchieved() }}日達成！
        </div>

        @if ($antiHabit->goal_achieved)
          <div class="badge badge-warning badge-lg flex items-center gap-1">
            <i class="fas fa-trophy text-xs"></i>
            目標達成
          </div>
        @endif
      </div>
    </div>

    <!-- 説明文（省略表示） -->
    @if (filled($antiHabit->description))
      <p class="text-base-content/70 mb-4">
        {{ \Illuminate\Support\Str::limit($antiHabit->description, 80) }}
      </p>
    @endif

    <!-- タグ -->
    @if ($antiHabit->tags->isNotEmpty())
      <div class="mb-4">
        <div class="flex flex-wrap gap-2">
          @foreach ($antiHabit->tags->take(3) as $tag)
            <div class="badge badge-outline badge-primary">
              <a href="{{ route('anti_habits.index', ['q' => ['tags_name_in' => $tag->name]]) }}">
                #{{ $tag->name }}
              </a>
            </div>
          @endforeach
          @if ($antiHabit->tags->count() > 3)
            <div class="badge badge-ghost">
              +{{ $antiHabit->tags->count() - 3 }}
            </div>
          @endif
        </div>
      </div>
    @endif

    <!-- リアクションボタン -->
    @auth
      <div class="flex items-center mt-4 space-x-1">
        @include('shared.reaction_buttons', ['antiHabit' => $antiHabit])
        <div class="flex items-center space-x-1">
          <i class="fas fa-comment"></i>
          {{ $antiHabit->comments_count }}
        </div>
      </div>
    @endauth
  </div>
</div>
