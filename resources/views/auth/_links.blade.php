@unless (request()->routeIs('password.*') || request()->routeIs('register'))
  <a href="{{ route('password.request') }}">パスワードをお忘れですか?</a><br />
@endunless

<form action="{{ route('line.redirect') }}" method="post" data-turbo="false">
  @csrf
  <button type="submit" class="w-full mt-3 px-4 py-2 bg-green-500 text-white rounded-xl hover:bg-green-600 transition font-semibold">LINEでログイン</button>
</form>
