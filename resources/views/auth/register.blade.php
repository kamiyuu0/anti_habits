@extends('layouts.app')

@section('content')
<div class="flex justify-center p-4">
  <div class="card shrink-0 w-full max-w-sm shadow-2xl bg-base-100 mt-16">
    <div class="card-body p-8">
      <div class="text-center mb-4">
        <h2 class="text-2xl font-bold">アカウント作成</h2>
      </div>

      <form action="{{ route('register.store') }}" method="post">
        @csrf
        @include('shared.form_errors')

        <div class="form-control mb-4">
          <label class="label" for="name">
            <span class="label-text">名前</span>
          </label>
          <input type="text" name="name" id="name" value="{{ old('name') }}"
              autofocus
              autocomplete="name"
              class="input input-bordered w-full"
              placeholder="お名前を入力してください">
        </div>

        <div class="form-control mb-4">
          <label class="label" for="email">
            <span class="label-text">メールアドレス</span>
          </label>
          <input type="email" name="email" id="email" value="{{ old('email') }}"
              autocomplete="email"
              class="input input-bordered w-full"
              placeholder="メールアドレスを入力してください">
        </div>

        <div class="form-control mb-4">
          <label class="label" for="password">
            <span class="label-text">パスワード</span>
            <span class="label-text-alt">（{{ \App\Models\User::MIN_PASSWORD_LENGTH }}文字以上）</span>
          </label>
          <input type="password" name="password" id="password"
              autocomplete="new-password"
              class="input input-bordered w-full"
              placeholder="パスワードを入力してください">
        </div>

        <div class="form-control mb-4">
          <label class="label" for="password_confirmation">
            <span class="label-text">パスワード確認</span>
          </label>
          <input type="password" name="password_confirmation" id="password_confirmation"
              autocomplete="new-password"
              class="input input-bordered w-full"
              placeholder="パスワードを再度入力してください">
        </div>

        <div class="form-control mt-6">
          <input type="submit" value="アカウント作成" class="btn btn-primary w-full">
        </div>
      </form>

      <div class="divider">または</div>

      <div class="text-center">
        @include('auth._links')
      </div>
    </div>
  </div>
</div>
@endsection
