@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-md px-4 py-8">
  <div class="card bg-base-100 shadow-xl">
    <div class="card-body">
      <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-base-content">プロフィール編集</h2>
      </div>

      <form action="{{ route('registration.update') }}" method="post" class="space-y-4">
        @csrf
        @method('PUT')
        @include('shared.form_errors')

        <div class="form-control">
          <div class="label">
            <span class="label-text">名前</span>
          </div>
          <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" autofocus class="input input-bordered w-full">
        </div>

        <div class="form-control mt-6">
          <input type="submit" value="更新" class="btn btn-primary w-full">
        </div>
      </form>

      <div class="divider"></div>

      <div class="form-control">
        <a href="{{ route('users.show', auth()->user()) }}" class="btn btn-outline w-full">戻る</a>
      </div>
    </div>
  </div>
</div>
@endsection
