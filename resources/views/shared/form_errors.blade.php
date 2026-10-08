{{-- 入力エラーの一覧表示。$errors (MessageBag) を受け取る --}}
@if ($errors->any())
  <div class="alert alert-error flex items-center">
    <i class="fas fa-exclamation-circle shrink-0 text-lg mr-3"></i>
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
