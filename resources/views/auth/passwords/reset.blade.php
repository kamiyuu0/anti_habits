@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-md px-4 py-8">
  <div class="card bg-base-100 shadow-xl">
    <div class="card-body">
      <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-base-content">パスワード変更</h2>
      </div>

      <form action="{{ route('password.update') }}" method="post">
        @csrf
        @method('PUT')
        @include('shared.form_errors')
        <input type="hidden" name="reset_password_token" value="{{ $token }}">

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
          <input type="submit" value="パスワードを変更する" class="btn btn-primary w-full">
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
