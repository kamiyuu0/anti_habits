<?php

namespace Tests\Feature\Models;

use App\Enums\ReactionKind;
use App\Models\AntiHabit;
use App\Models\Comment;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function devise_で作成した_bcrypt_ハッシュでログインできる(): void
    {
        // bcrypt-ruby (Devise) が生成した "$2a$" 形式のハッシュ。平文は "password123"
        $user = User::factory()->create([
            'email' => 'legacy@example.com',
            'encrypted_password' => '$2a$12$hLzZGKkFz.osFGj6hsZ6pen7QBahDoJ6.hytJ1buadVPTuKgffB0S',
        ]);

        $this->assertTrue(Auth::validate(['email' => 'legacy@example.com', 'password' => 'password123']));
        $this->assertFalse(Auth::validate(['email' => 'legacy@example.com', 'password' => 'wrong']));
        // ハッシュ形式は書き換えない
        $this->assertSame('$2a$12$hLzZGKkFz.osFGj6hsZ6pen7QBahDoJ6.hytJ1buadVPTuKgffB0S', $user->fresh()->encrypted_password);
    }

    #[Test]
    public function remember_token_は_remember_created_at_から導出され更新で無効になる(): void
    {
        $user = User::factory()->create();
        $this->assertNull($user->getRememberToken());

        $user->setRememberToken('dummy');
        $user->save();
        $token = $user->fresh()->getRememberToken();
        $this->assertNotEmpty($token);
        $this->assertTrue($user->is(Auth::getProvider()->retrieveByToken($user->id, $token)));

        $this->travel(1)->seconds();
        $user->setRememberToken('another');
        $user->save();
        $this->assertNull(Auth::getProvider()->retrieveByToken($user->id, $token));
    }

    #[Test]
    public function own_は自分の投稿かどうかを返す(): void
    {
        $user = User::factory()->create();
        $mine = AntiHabit::factory()->for($user)->create();
        $others = AntiHabit::factory()->create();

        $this->assertTrue($user->own($mine));
        $this->assertFalse($user->own($others));
        $this->assertFalse($user->own(null));
    }

    #[Test]
    public function リアクションの付け外しができ重複しない(): void
    {
        $user = User::factory()->create();
        $antiHabit = AntiHabit::factory()->create();

        $user->reaction($antiHabit, ReactionKind::Fire);
        $user->reaction($antiHabit, ReactionKind::Fire);
        $user->reaction($antiHabit, ReactionKind::Zen);

        $this->assertSame(2, $user->reactions()->count());
        $this->assertTrue($user->hasReacted($antiHabit, ReactionKind::Fire));
        $this->assertFalse($user->hasReacted($antiHabit, ReactionKind::Watching));
        $this->assertDatabaseHas('reactions', ['user_id' => $user->id, 'reaction_kind' => 3]);

        $user->unreaction($antiHabit, ReactionKind::Fire);
        $this->assertFalse($user->hasReacted($antiHabit, ReactionKind::Fire));
        $this->assertSame(1, $antiHabit->reactionCount(ReactionKind::Zen));
    }

    #[Test]
    public function ブックマークの付け外しができる(): void
    {
        $user = User::factory()->create();
        $antiHabit = AntiHabit::factory()->create();

        $user->bookmark($antiHabit);
        $user->bookmark($antiHabit);
        $this->assertSame(1, $user->bookmarks()->count());
        $this->assertTrue($user->hasBookmarked($antiHabit));

        $user->unbookmark($antiHabit);
        $this->assertFalse($user->hasBookmarked($antiHabit));
    }

    #[Test]
    public function 自分の投稿と非公開の投稿はブックマークできない(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->canBookmark(AntiHabit::factory()->create()));
        $this->assertFalse($user->canBookmark(AntiHabit::factory()->for($user)->create()));
        $this->assertFalse($user->canBookmark(AntiHabit::factory()->private()->create()));
    }

    #[Test]
    public function ユーザー削除で関連データも削除される(): void
    {
        $user = User::factory()->create();
        $antiHabit = AntiHabit::factory()->for($user)->create();
        NotificationSetting::factory()->for($antiHabit)->create();
        $user->reaction(AntiHabit::factory()->create(), ReactionKind::Fire);
        $user->bookmark(AntiHabit::factory()->create());
        Comment::factory()->for($user)->create();

        $user->delete();

        $this->assertModelMissing($user);
        $this->assertModelMissing($antiHabit);
        $this->assertDatabaseCount('reactions', 0);
        $this->assertDatabaseCount('bookmarks', 0);
        $this->assertDatabaseCount('comments', 0);
    }
}
