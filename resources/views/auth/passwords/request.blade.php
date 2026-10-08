@extends('layouts.app')

@section('content')
<div class="flex justify-center p-4">
  <div class="card shrink-0 w-full max-w-sm shadow-2xl bg-base-100 mt-16">
    <div class="card-body p-8">
      <div class="text-center mb-6">
        <h2 class="text-2xl font-bold">パスワードリセット </h2>
      </div>

      <form action="{{ route('password.email') }}" method="post">
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

        <div class="form-control mt-6">
          <input type="submit" value="リセットメールを送る" class="btn btn-primary w-full">
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
