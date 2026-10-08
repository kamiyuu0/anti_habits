@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-4xl px-4 py-8">
  <!-- マイページのタイトル -->
  <div class="text-center mb-8">
    <h1 class="text-3xl font-bold text-base-content">マイページ</h1>
  </div>

  <!-- ユーザー情報 -->
  <div class="card bg-base-100 shadow-xl mb-8">
    <div class="card-body">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="flex items-center">
            <i class="fas fa-user text-2xl mr-3"></i>
          </div>
          <div>
            <h2 class="text-xl font-semibold text-base-content">{{ $user->name }}</h2>
          </div>
        </div>

        <!-- ユーザー編集ボタン -->
        <div class="flex flex-col gap-2 w-full sm:w-auto">
          <a href="{{ route('registration.edit') }}" class="btn btn-outline btn-sm w-full">プロフィール編集</a>
          <form action="{{ route('line.redirect') }}" method="post" data-turbo="false">
            @csrf
            <button type="submit" class="btn btn-success btn-sm w-full">LINEで認証</button>
          </form>
          <a href="{{ config('services.line_messaging.friend_url') }}" class="btn btn-success btn-sm w-full">
            LINE友だち追加
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- 過去の悪習慣投稿 -->
  <div class="mb-8">
    <h2 class="text-2xl font-bold text-base-content mb-6">過去の悪習慣投稿</h2>

    <div class="space-y-4">
      @forelse ($antiHabits as $antiHabit)
        @include('anti_habits._card', ['antiHabit' => $antiHabit])
      @empty
        <!-- 空の状態 -->
        <div class="card bg-base-100 shadow-md">
          <div class="card-body text-center py-12">
            <div class="mb-4">
              <i class="fas fa-plus-circle text-4xl text-primary"></i>
            </div>
            <h3 class="text-lg font-bold text-base-content mb-2">
              まだ悪習慣が投稿されていません
            </h3>
            <p class="text-base-content/70 mb-4">最初の悪習慣を登録してみましょう</p>
          </div>
        </div>
      @endforelse
    </div>
  </div>
</div>
@endsection
