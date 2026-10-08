@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
  <div class="mb-8">
    <h1 class="text-4xl font-bold text-center mb-4">ブックマーク一覧</h1>
    <p class="text-center text-base-content/70">
      気になる悪習慣をチェックしましょう
    </p>
  </div>

  <div class="space-y-6">
    @forelse ($antiHabits as $antiHabit)
      @include('anti_habits._card', ['antiHabit' => $antiHabit])
    @empty
      <!-- 空の状態 -->
      <div class="hero min-h-96">
        <div class="hero-content text-center">
          <div class="max-w-md">
            <div class="mb-6">
              <i class="fas fa-bookmark text-8xl text-base-content/30"></i>
            </div>
            <h3 class="text-2xl font-bold mb-4">
              まだブックマークがありません
            </h3>
            <p class="mb-6">
              気になる悪習慣をブックマークして、いつでも確認できるようにしましょう
            </p>
            <a href="{{ route('anti_habits.index') }}" class="btn btn-primary">悪習慣一覧を見る</a>
          </div>
        </div>
      </div>
    @endforelse
  </div>

  <!-- ページネーション -->
  {{ $antiHabits->onEachSide(2)->links() }}
</div>
@endsection
