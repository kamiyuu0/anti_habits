<?php

namespace Tests\Feature\Http;

use App\Mail\PasswordChanged;
use App\Mail\ResetPasswordInstructions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ---- 新規登録 ----

    #[Test]
    public function 新規登録するとログインして一覧へ移動する(): void
    {
        $this->get('/users/sign_up')->assertOk();

        $this->post('/users', [
            'name' => '新規ユーザー',
            'email' => ' New@Example.com ',
            'password' => 'secret1',
            'password_confirmation' => 'secret1',
        ])->assertRedirect('/anti_habits')->assertSessionHas('notice');

        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('new@example.com', $user->email);
        $this->assertTrue(password_verify('secret1', $user->encrypted_password));
    }

    #[Test]
    public function 新規登録のバリデーション(): void
    {
        User::factory()->create(['name' => '既存', 'email' => 'taken@example.com']);

        $this->from('/users/sign_up')->post('/users', [
            'name' => str_repeat('あ', 11), 'email' => 'invalid', 'password' => '123', 'password_confirmation' => '456',
        ])->assertSessionHasErrors(['name', 'email', 'password']);

        // メールアドレス重複時は他のエラーも含めて汎用メッセージのみにする
        $this->from('/users/sign_up')->post('/users', [
            'name' => '既存', 'email' => 'TAKEN@example.com', 'password' => 'secret1', 'password_confirmation' => 'secret1',
        ]);
        $this->get('/users/sign_up')->assertSee('登録できませんでした。')->assertDontSee('名前はすでに存在します');
        $this->assertSame(1, User::count());
    }

    // ---- ログイン / ログアウト ----

    #[Test]
    public function ログインするとマイページへ移動する(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);

        $this->post('/users/sign_in', ['email' => 'ME@example.com', 'password' => 'password'])
            ->assertRedirect("/users/{$user->id}")
            ->assertSessionHas('notice', 'ログインしました。');
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function ログイン失敗時はエラーを表示する(): void
    {
        User::factory()->create(['email' => 'me@example.com']);

        $this->from('/users/sign_in')
            ->post('/users/sign_in', ['email' => 'me@example.com', 'password' => 'wrong'])
            ->assertRedirect('/users/sign_in')
            ->assertSessionHas('alert', 'メールアドレスまたはパスワードが正しくありません。');
        $this->assertGuest();
    }

    #[Test]
    public function ログイン状態を保持するとremember_cookie_が発行される(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);

        $response = $this->post('/users/sign_in', ['email' => 'me@example.com', 'password' => 'password', 'remember_me' => '1']);

        $this->assertNotNull($user->fresh()->remember_created_at);
        $this->assertNotEmpty(collect($response->headers->getCookies())->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_')));
    }

    #[Test]
    public function ログイン済みでログイン画面に来るとマイページへ(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/users/sign_in')->assertRedirect("/users/{$user->id}");
    }

    #[Test]
    public function ログアウトできる(): void
    {
        $this->actingAs(User::factory()->create())
            ->delete('/users/sign_out')
            ->assertStatus(303)
            ->assertRedirect('/');
        $this->assertGuest();
    }

    // ---- プロフィール編集 ----

    #[Test]
    public function 名前を変更できる(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['name' => '使用済み']);

        $this->actingAs($user)->get('/users/edit')->assertOk();
        $this->actingAs($user)->put('/users', ['name' => '使用済み'])->assertSessionHasErrors('name');
        $this->actingAs($user)->put('/users', ['name' => '新しい名前'])
            ->assertRedirect("/users/{$user->id}")
            ->assertSessionHas('notice', 'アカウント情報を更新しました。');
        $this->assertSame('新しい名前', $user->fresh()->name);
    }

    // ---- パスワード再設定 ----

    #[Test]
    public function パスワードを再設定できる(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'me@example.com']);

        $this->post('/users/password', ['email' => 'unknown@example.com'])->assertSessionHasErrors('email');

        $this->post('/users/password', ['email' => 'me@example.com'])
            ->assertRedirect('/users/sign_in')
            ->assertSessionHas('notice');

        $token = null;
        Mail::assertSent(ResetPasswordInstructions::class, function ($mail) use (&$token) {
            $token = $mail->token;

            return $mail->hasTo('me@example.com');
        });
        $this->assertNotNull($user->fresh()->reset_password_token);
        $this->assertNotSame($token, $user->fresh()->reset_password_token); // ダイジェストを保存

        $this->get('/users/password/edit')->assertRedirect('/users/sign_in');
        $this->get("/users/password/edit?reset_password_token={$token}")->assertOk();

        $this->put('/users/password', ['reset_password_token' => 'wrong', 'password' => 'newpass', 'password_confirmation' => 'newpass'])
            ->assertSessionHasErrors('reset_password_token');

        $this->put('/users/password', ['reset_password_token' => $token, 'password' => 'newpass', 'password_confirmation' => 'newpass'])
            ->assertRedirect('/');

        $user->refresh();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(password_verify('newpass', $user->encrypted_password));
        $this->assertNull($user->reset_password_token);
        Mail::assertSent(PasswordChanged::class);
    }

    #[Test]
    public function 期限切れの再設定トークンは使えない(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'me@example.com']);
        $this->post('/users/password', ['email' => 'me@example.com']);
        $token = null;
        Mail::assertSent(ResetPasswordInstructions::class, function ($mail) use (&$token) {
            $token = $mail->token;

            return true;
        });

        $this->travel(7)->hours();

        $this->put('/users/password', ['reset_password_token' => $token, 'password' => 'newpass', 'password_confirmation' => 'newpass'])
            ->assertSessionHasErrors('reset_password_token');
        $this->assertGuest();
    }

    // ---- LINE ログイン ----

    private function mockLineUser(string $id, string $name = 'LINEユーザー'): void
    {
        $lineUser = (new SocialiteUser)->map(['id' => $id, 'name' => $name, 'email' => null]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('setScopes')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($lineUser);
        Socialite::shouldReceive('driver')->with('line')->andReturn($provider);
    }

    #[Test]
    public function line_ログインで初回はユーザーが作成される(): void
    {
        $this->mockLineUser('U123');

        $this->get('/users/auth/line/callback')->assertRedirect('/anti_habits')->assertSessionHas('notice', 'ログインしました');

        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(['line', 'U123', 'U123-line@example.com', 'LINEユーザー'], [$user->provider, $user->uid, $user->email, $user->name]);

        // 2 回目は同じユーザーでログイン
        auth()->logout();
        $this->get('/users/auth/line/callback');
        $this->assertSame(1, User::count());
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function ログイン済みなら_line_アカウントを紐付ける(): void
    {
        $user = User::factory()->create();
        $this->mockLineUser('U999');

        $this->actingAs($user)->get('/users/auth/line/callback')
            ->assertRedirect("/users/{$user->id}")
            ->assertSessionHas('notice', 'LINEアカウントを紐付けました');
        $this->assertSame(['line', 'U999'], [$user->fresh()->provider, $user->fresh()->uid]);

        $other = User::factory()->create();
        $this->actingAs($other)->get('/users/auth/line/callback')
            ->assertSessionHas('alert', 'このLINEアカウントは既に登録されています');
        $this->assertNull($other->fresh()->uid);
    }

    #[Test]
    public function line_認証の開始は_post_のみ(): void
    {
        $this->get('/users/auth/line')->assertMethodNotAllowed();
    }
}
