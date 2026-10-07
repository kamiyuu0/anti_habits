{{-- エラーメッセージを表示 --}}
<turbo-stream action="replace" target="comment-form">
  <template>@include('comments._form', ['antiHabit' => $antiHabit, 'formErrors' => $errors, 'body' => $body])</template>
</turbo-stream>
