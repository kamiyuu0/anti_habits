{{-- リストの一番上に追加 --}}
<turbo-stream action="prepend" target="comments-list">
  <template>@include('comments._comment', ['comment' => $comment])</template>
</turbo-stream>

{{-- formを空で更新 --}}
<turbo-stream action="replace" target="comment-form">
  <template>@include('comments._form', ['antiHabit' => $antiHabit])</template>
</turbo-stream>

{{-- コメント数を更新 --}}
<turbo-stream action="update" target="comments-count">
  <template>{{ $antiHabit->comments_count }}</template>
</turbo-stream>
<turbo-stream action="update" target="comments-count2">
  <template>{{ $antiHabit->comments_count }}</template>
</turbo-stream>
