<div class="navbar bg-base-100">
  <div class="navbar-start">
    <div class="btn btn-ghost text-xl cursor-default">
      <a href="{{ auth()->check() ? route('anti_habits.index') : route('root') }}">
        <span class="inline ml-2">Anti Habits</span>
      </a>
    </div>
  </div>

  <div class="navbar-end">
    <div class="dropdown dropdown-end">
      <div tabindex="0" role="button" class="btn btn-square btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-5 h-5 stroke-current">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </div>
      <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
        @auth
          <li><a href="{{ route('root') }}">アプリについて</a></li>
          <li><a href="{{ route('terms') }}">利用規約</a></li>
          <li><a href="{{ route('privacy') }}">プライバシーポリシー</a></li>
          <li><a href="https://forms.gle/x7umjBWhLLq82aaF6" target="_blank" rel="noopener noreferrer">お問い合わせ</a></li>
          <li><a href="{{ route('logout') }}" data-turbo-method="delete">ログアウト</a></li>
        @else
          <li><a href="{{ route('login') }}">ログイン</a></li>
          <li><a href="{{ route('register') }}">新規登録</a></li>
          <li><a href="{{ route('terms') }}">利用規約</a></li>
          <li><a href="{{ route('privacy') }}">プライバシーポリシー</a></li>
          <li><a href="https://forms.gle/x7umjBWhLLq82aaF6" target="_blank" rel="noopener noreferrer">お問い合わせ</a></li>
        @endauth
      </ul>
    </div>
  </div>
</div>
