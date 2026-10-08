<div class="dock">
  <!-- ホーム -->
  <a href="{{ auth()->check() ? route('anti_habits.index') : route('root') }}">
    <button>
      <i class="fas fa-home text-lg"></i>
      <span class="dock-label">ホーム</span>
    </button>
  </a>

  @auth
    <!-- ブックマーク（ログイン時のみ） -->
    <a href="{{ route('bookmarks.index') }}">
      <button>
        <i class="fas fa-bookmark text-lg"></i>
        <span class="dock-label">ブックマーク</span>
      </button>
    </a>

    <!-- 新規投稿 -->
    <a href="{{ route('anti_habits.create') }}">
      <button>
        <i class="fas fa-plus text-lg"></i>
        <span class="dock-label">新規投稿</span>
      </button>
    </a>

    <!-- マイページ -->
    <a href="{{ route('users.show', auth()->user()) }}">
      <button>
        <i class="fas fa-user text-lg"></i>
        <span class="dock-label">マイページ</span>
      </button>
    </a>
  @else
    <!-- 新規登録 -->
    <a href="{{ route('register') }}">
      <button>
        <i class="fas fa-user-plus text-lg"></i>
        <span class="dock-label">新規登録</span>
      </button>
    </a>

    <!-- ログイン -->
    <a href="{{ route('login') }}">
      <button>
        <i class="fas fa-sign-in-alt text-lg"></i>
        <span class="dock-label">ログイン</span>
      </button>
    </a>
  @endauth
</div>
