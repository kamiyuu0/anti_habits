@extends('layouts.app')

@section('content')
<div class="flex justify-center p-4">
  <div class="card shrink-0 w-full max-w-sm shadow-2xl bg-base-100 mt-16">
    <div class="card-body p-8">
      <div class="text-center mb-6">
        <h2 class="text-2xl font-bold">ログイン</h2>
      </div>

      <form action="{{ route('login.store') }}" method="post">
        @csrf
        @include('shared.form_errors')

        <div class="form-control mb-4">
          <label class="label" for="email">
            <span class="label-text">メールアドレス</span>
          </label>
          <input type="email" name="email" id="email" value="{{ old('email') }}"
              autofocus
              autocomplete="email"
              class="input input-bordered w-full"
              placeholder="メールアドレスを入力してください">
        </div>

        <div class="form-control mb-4">
          <label class="label" for="password">
            <span class="label-text">パスワード</span>
          </label>
          <input type="password" name="password" id="password"
              autocomplete="current-password"
              class="input input-bordered w-full"
              placeholder="パスワードを入力してください">
        </div>

        <div class="form-control mb-4">
          <label class="label cursor-pointer">
            <input type="hidden" name="remember_me" value="0">
            <input type="checkbox" name="remember_me" value="1" class="checkbox" @checked(old('remember_me') === '1')>
            <span class="label-text ml-2">ログイン状態を保持する</span>
          </label>
        </div>

        <div class="form-control mt-6">
          <input type="submit" value="ログイン" class="btn btn-primary w-full">
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
