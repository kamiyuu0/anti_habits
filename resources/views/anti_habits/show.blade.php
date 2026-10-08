@extends('layouts.app')

@section('ogp_image', $ogpImageUrl)

@section('content')
@php($currentUser = auth()->user())
<div class="container mx-auto px-4 py-6">
  <!-- メイン投稿 -->
  <div class="card bg-base-100 shadow-xl rounded-2xl border border-base-300 mb-8">
    <div class="card-body p-8 relative">
      <!-- ブックマークボタン（右上） -->
      @if ($currentUser?->canBookmark($antiHabit))
        <div class="absolute top-4 right-4">
          @include('shared.bookmark_button', ['antiHabit' => $antiHabit])
        </div>
      @endif

      <!-- ユーザー情報 -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <i class="fas fa-user text-lg mr-3"></i>
            <div>
              <h3 class="font-semibold text-lg">{{ $antiHabit->user->name }}</h3>
            </div>
        </div>

        <!-- 本人マーク・非公開バッジ -->
        <div class="flex gap-2">
          @if ($isOwner)
            <div class="badge badge-info text-[8px] sm:text-sm">
              あなたの投稿
            </div>
            @if (! $antiHabit->is_public)
              <div class="badge badge-warning text-[8px] sm:text-sm">
                非公開
              </div>
            @endif
          @endif
        </div>
      </div>

      <!-- タイトル -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold mb-2">
          {{ $antiHabit->title }}
        </h1>

        <div class="flex flex-wrap gap-2">
          <div class="badge badge-success badge-lg">
            連続{{ $antiHabit->consecutiveDaysAchieved() }}日達成！
          </div>

          @if ($antiHabit->goal_days !== null)
            <div class="badge badge-outline badge-lg">
              目標：{{ $antiHabit->goal_days }}日
            </div>
          @endif

          @if ($antiHabit->goal_achieved)
            <div class="badge badge-warning badge-lg flex items-center gap-1">
              <i class="fas fa-trophy text-sm"></i>
              目標達成
            </div>
          @endif
        </div>
      </div>

      <!-- ヒートマップセクション（本人のみ、PCのみ表示） -->
      @if ($isOwner && $calendarData)
        <div class="mb-6 hidden md:block">
          <div class="card bg-base-100 border border-base-300 rounded-lg">
            <div class="card-body p-4 sm:p-6">
              <h2 class="card-title text-lg sm:text-xl mb-4">
                <i class="fas fa-calendar-check mr-2"></i>
                活動記録（過去3ヶ月間）
              </h2>
              <div style="height: 180px; width: 100%;"
                   data-controller="calendar-chart"
                   data-calendar-chart-data-value='@json($calendarData)'
                   data-calendar-chart-start-value="{{ $calendarData[0][0] }}"
                   data-calendar-chart-end-value="{{ $calendarData[count($calendarData) - 1][0] }}"></div>
            </div>
          </div>
        </div>
      @endif

      <!-- 説明 -->
      <div class="mb-6">
        <div class="prose max-w-none">
          <p class="text-base-content/80 leading-relaxed whitespace-pre-wrap">{{ $antiHabit->description }}</p>
        </div>
      </div>

      <!-- タグ -->
      @if ($antiHabit->tags->isNotEmpty())
        <div class="mb-6">
          <div class="flex flex-wrap gap-2">
            @foreach ($antiHabit->tags as $tag)
              <div class="badge badge-outline badge-primary">
                <a href="{{ route('anti_habits.index', ['q' => ['tags_name_in' => $tag->name]]) }}">
                  #{{ $tag->name }}
                </a>
              </div>
            @endforeach
          </div>
        </div>
      @endif

      <!-- 記録トグル（本人のみ） -->
      @if ($isOwner)
        <div class="mb-6">
          <div class="text-center mb-4">
            <span class="text-lg font-medium">今日の記録：</span>
          </div>

          <div class="flex flex-col space-y-4">
            @if ($todayRecord)
              <!-- 記録済みの状態 -->
              <div class="alert alert-success flex flex-col items-center justify-center text-center">
                <div class="flex items-center space-x-2">
                  <span class="text-2xl">💪</span>
                  <span class="font-bold text-lg">我慢できた</span>
                </div>
                <form action="{{ route('anti_habit_records.destroy', $todayRecord) }}" method="post" data-turbo-confirm="記録を削除しますか？">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-neutral btn-sm mt-3">記録を削除</button>
                </form>
              </div>
            @else
              <!-- 我慢できたボタン -->
              <div class="card bg-success/10 border border-success/20 hover:bg-success/20 transition-colors cursor-pointer">
                <form action="{{ route('anti_habit_records.store') }}" method="post">
                  @csrf
                  <input type="hidden" name="anti_habit_id" value="{{ $antiHabit->id }}">
                  <button type="submit" class="w-full bg-transparent border-none p-0">
                    <div class="card-body flex items-center justify-center p-6">
                      <div class="flex items-center space-x-2">
                        <span class="text-2xl">💪</span>
                        <span class="font-bold text-lg text-success">我慢できた</span>
                      </div>
                    </div>
                  </button>
                </form>
              </div>
            @endif
          </div>
        </div>

        <!-- 編集・削除ボタン -->
        <div class="card-actions justify-end">
          @if ($currentUser->isLineLinked())
            <a href="{{ $antiHabit->notificationSetting ? route('anti_habits.notification_setting.edit', $antiHabit) : route('anti_habits.notification_setting.create', $antiHabit) }}" class="btn btn-outline btn-primary">LINE通知設定</a>
          @else
            <button class="btn btn-outline btn-primary" onclick="line_auth_modal.showModal()">LINE通知設定</button>
            <dialog id="line_auth_modal" class="modal">
              <div class="modal-box">
                <p class="py-4">LINE通知にするにはLINE認証してください。</p>
                <form action="{{ route('line.redirect') }}" method="post" data-turbo="false">
                  @csrf
                  <button type="submit" class="btn btn-success btn-sm w-full">LINEで認証</button>
                </form>
              </div>
              <form method="dialog" class="modal-backdrop">
                <button></button>
              </form>
            </dialog>
          @endif
          @if ($antiHabit->is_public)
            <a href="https://twitter.com/intent/tweet?url={{ route('anti_habits.show', $antiHabit) }}&text={{ urlencode('Anti Habitsで悪習慣を登録！') }}%0A{{ urlencode('悪習慣を辞められるか監視してください！') }}%0A&hashtags={{ urlencode('AntiHabits') }}"
               target="_blank"
               rel="noopener noreferrer"
               class="btn btn-outline btn-neutral">Xでシェアする</a>
          @endif
          <a href="{{ route('anti_habits.edit', $antiHabit) }}" class="btn btn-outline btn-primary">編集</a>
          <form action="{{ route('anti_habits.destroy', $antiHabit) }}" method="post" class="inline" data-turbo-confirm="本当に削除しますか？">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline btn-error">削除</button>
          </form>
        </div>
      @endif

      <!-- リアクションボタン（公開時のみ表示） -->
      @if ($currentUser && $antiHabit->is_public)
        <div class="mt-4">
          @include('shared.reaction_buttons', ['antiHabit' => $antiHabit])
        </div>
        <div class="flex items-center mt-2 space-x-4">
          <div class="flex items-center space-x-1">
            <i class="fas fa-comment"></i>
            <span id="comments-count">{{ $antiHabit->comments_count }}</span>件
          </div>
        </div>
      @endif
    </div>
  </div>

  <!-- コメントセクション（公開かつログイン時のみ表示） -->
  @if ($currentUser && $antiHabit->is_public)
    <div class="card bg-base-100 shadow-xl rounded-2xl border border-base-300">
      <div class="card-body p-8">
        <h2 class="card-title text-2xl mb-6">
          応援メッセージ
          <div class="badge badge-neutral">
            <span id="comments-count2">{{ $antiHabit->comments_count }}</span>件
          </div>
        </h2>

        <!-- 新規コメントフォーム -->
        <div class="mb-6">
          @include('comments._form', ['antiHabit' => $antiHabit])
        </div>

        <!-- 既存コメント一覧 -->
        <div class="space-y-4">
          <div id="comments-list">
            @forelse ($comments as $comment)
              @include('comments._comment', ['comment' => $comment])
            @empty
              <div class="hero min-h-32">
                <div class="hero-content text-center">
                  <div>
                    <p class="text-base-content/60 mb-2">
                      まだ応援メッセージはありません
                    </p>
                    <p class="text-base-content/60 text-sm">
                      最初の応援メッセージを投稿してみませんか？
                    </p>
                  </div>
                </div>
              </div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  @endif
</div>
@endsection
