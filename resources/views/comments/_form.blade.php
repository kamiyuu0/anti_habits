{{-- $formErrors (MessageBag|null) と $body (string|null) は Turbo Stream でのエラー表示時のみ渡される --}}
<div id="comment-form">
  <form action="{{ route('anti_habits.comments.store', $antiHabit) }}" method="post" class="space-y-4" data-turbo-stream="true">
    @csrf

    <!-- エラーメッセージ -->
    @if (! empty($formErrors) && $formErrors->any())
      @include('shared.form_errors', ['errors' => $formErrors])
    @endif

    <!-- コメント入力 -->
    <div class="form-control">
      <div class="label">
        <span class="label-text">応援メッセージ</span>
      </div>
      <textarea name="body"
          rows="4"
          class="textarea textarea-bordered w-full"
          placeholder="頑張っていますね！応援しています！"
          maxlength="{{ \App\Models\Comment::MAX_BODY_LENGTH }}">{{ $body ?? '' }}</textarea>
      <div class="label">
        <span class="label-text-alt">最大500文字</span>
      </div>
    </div>

    <!-- 送信ボタン -->
    <div class="form-control">
      <input type="submit" value="応援メッセージを送る" class="btn btn-success">
    </div>
  </form>
</div>
