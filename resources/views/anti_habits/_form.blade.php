@php
    $isPersisted = $antiHabit->exists;
    $tagNames = old('tag_names', $isPersisted ? $antiHabit->tagNamesAsString() : '');
    $isPublic = old('is_public', $antiHabit->is_public);
@endphp
<form action="{{ $isPersisted ? route('anti_habits.update', $antiHabit) : route('anti_habits.store') }}" method="post" class="space-y-6">
  @csrf
  @if ($isPersisted)
    @method('PATCH')
  @endif

  <!-- エラーメッセージ -->
  @if ($errors->any())
    <div class="alert alert-error">
      <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <div>
        <h3 class="font-bold">入力内容を確認してください</h3>
        <div>
          @foreach ($errors->all() as $message)
            <div>{{ $message }}</div>
          @endforeach
        </div>
      </div>
    </div>
  @endif

  <!-- タイトル -->
  <div class="form-control">
    <div class="label">
      <span class="label-text font-semibold">悪習慣のタイトル</span>
    </div>
    <input type="text" name="title" value="{{ old('title', $antiHabit->title) }}"
        class="input input-bordered w-full"
        placeholder="例：休日の前日につい夜更かししてしまう"
        maxlength="{{ \App\Models\AntiHabit::MAX_TITLE_LENGTH }}">
  </div>

  <!-- 説明 -->
  <div class="form-control">
    <div class="label">
      <span class="label-text font-semibold">詳細説明</span>
    </div>
    <textarea name="description" rows="5"
        class="textarea textarea-bordered w-full"
        placeholder="例:YouTubeやSNSを見てしまい深夜まで起きている。翌日の仕事に支障が出ている。"
        maxlength="{{ \App\Models\AntiHabit::MAX_DESCRIPTION_LENGTH }}">{{ old('description', $antiHabit->description) }}</textarea>
  </div>

  <!-- タグ -->
  <div class="form-control"
       data-controller="tagify"
       data-tagify-tags-url-value="{{ route('tags.index') }}"
       data-tagify-initial-value-value="{{ $tagNames }}">
    <div class="label">
      <span class="label-text font-semibold">タグ</span>
    </div>
    <input type="text" name="tag_names"
        class="input input-bordered w-full"
        placeholder="例：夜更かし, スマホ依存, SNS"
        value="{{ $tagNames }}">
  </div>

  <!-- 目標達成日数 -->
  <div class="form-control">
    <div class="label">
      <span class="label-text font-semibold">目標達成日数（任意）</span>
      <div class="badge badge-soft badge-xs tooltip" data-tip="目標日数を設定すると、達成時に達成感を得られます（1〜365日）。
設定しなくても記録は続けられます。">
        ?
      </div>
    </div>
    <input type="number" name="goal_days" value="{{ old('goal_days', $antiHabit->goal_days) }}"
        class="input input-bordered w-full"
        placeholder="例：30"
        min="1"
        max="365">
  </div>

  <!-- 公開設定 -->
  <div class="form-control">
    <label class="label cursor-pointer justify-start gap-4">
      <input type="hidden" name="is_public" value="0">
      <input type="checkbox" name="is_public" value="1" class="checkbox checkbox-primary" @checked($isPublic)>
      <span class="label-text font-semibold">この悪習慣を公開する</span>
    </label>
  </div>

  <!-- 送信ボタン -->
  <div class="form-control">
    <div class="flex flex-col sm:flex-row gap-3">
      <input type="submit" value="{{ $isPersisted ? '更新する' : '登録する' }}" class="btn btn-primary flex-1">
      <a href="{{ $isPersisted ? route('anti_habits.show', $antiHabit) : route('anti_habits.index') }}" class="btn btn-outline flex-1">キャンセル</a>
    </div>
  </div>
</form>
