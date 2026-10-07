<?php

namespace Tests\Feature\Http;

use App\Models\AntiHabit;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AntiHabitsTest extends TestCase
{
    use RefreshDatabase;

    private function validParams(array $overrides = []): array
    {
        return array_merge([
            'title' => '禁煙',
            'description' => '健康のために禁煙します',
            'tag_names' => '',
            'is_public' => '1',
            'goal_days' => '30',
        ], $overrides);
    }

    // ---- index / show ----

    #[Test]
    public function 一覧には公開中のものだけが表示される(): void
    {
        $public = AntiHabit::factory()->create(['title' => '公開の悪習慣']);
        AntiHabit::factory()->private()->create(['title' => '非公開の悪習慣']);

        $this->get('/anti_habits')
            ->assertOk()
            ->assertSee($public->title)
            ->assertDontSee('非公開の悪習慣');
    }

    #[Test]
    public function 一覧はキーワードとタグで絞り込める(): void
    {
        $tag = Tag::factory()->create(['name' => '睡眠']);
        AntiHabit::factory()->hasAttached($tag)->create(['title' => '夜更かし']);
        AntiHabit::factory()->create(['title' => '間食', 'description' => '夜にお菓子']);
        AntiHabit::factory()->create(['title' => '二度寝', 'description' => '朝起きられない']);

        $this->get('/anti_habits?q[title_or_description_cont]=夜')
            ->assertSee('夜更かし')->assertSee('間食')->assertDontSee('二度寝');

        $this->get('/anti_habits?q[tags_name_in]=睡眠')
            ->assertSee('夜更かし')->assertDontSee('間食');

        $this->get('/anti_habits?q[title_or_description_cont]=存在しない')
            ->assertSee('検索結果が見つかりませんでした');
    }

    #[Test]
    public function 一覧は10件ずつページングされる(): void
    {
        AntiHabit::factory()->count(11)->create();

        $this->get('/anti_habits')->assertOk()->assertSee('次へ');
        $this->get('/anti_habits?page=2')->assertOk()->assertSee('前へ');
    }

    #[Test]
    public function オートコンプリートは公開中のタイトルを返す(): void
    {
        AntiHabit::factory()->create(['title' => '夜更かし']);
        AntiHabit::factory()->private()->create(['title' => '夜食']);

        $this->get('/anti_habits/autocomplete?q=夜')
            ->assertOk()
            ->assertSee('data-autocomplete-value="夜更かし"', false)
            ->assertDontSee('夜食');

        $this->assertSame('', trim($this->get('/anti_habits/autocomplete?q=')->getContent()));
    }

    #[Test]
    public function 詳細は公開なら誰でも閲覧でき_非公開は本人のみ(): void
    {
        $owner = User::factory()->create();
        $public = AntiHabit::factory()->for($owner)->create();
        $private = AntiHabit::factory()->for($owner)->private()->create();

        $this->get("/anti_habits/{$public->id}")->assertOk()->assertSee($public->title);

        $this->get("/anti_habits/{$private->id}")
            ->assertRedirect('/anti_habits')
            ->assertSessionHas('alert', 'このページにアクセスする権限がありません。');
        $this->actingAs(User::factory()->create())->get("/anti_habits/{$private->id}")->assertRedirect('/anti_habits');
        $this->actingAs($owner)->get("/anti_habits/{$private->id}")->assertOk()->assertSee('あなたの投稿')->assertSee('非公開');
    }

    #[Test]
    public function 詳細ページは動的_ogp_画像を設定する(): void
    {
        $antiHabit = AntiHabit::factory()->create(['title' => 'スマホ']);

        $this->get("/anti_habits/{$antiHabit->id}")
            ->assertSee('<meta property="og:image" content="https://res.cloudinary.com/antihabits/image/upload/l_text:Sawarabi%20Gothic_50_solid:スマホ', false);
    }

    #[Test]
    public function 存在しない_id_はリダイレクトされる(): void
    {
        $this->get('/anti_habits/0')->assertRedirect('/')->assertSessionHas('alert', '指定されたページが見つかりません。');
        $this->actingAs(User::factory()->create())->get('/anti_habits/0')->assertRedirect('/anti_habits');
    }

    #[Test]
    public function 本人の詳細ページにはヒートマップと記録ボタンが表示される(): void
    {
        $owner = User::factory()->create();
        $antiHabit = AntiHabit::factory()->for($owner)->create();

        $this->actingAs($owner)->get("/anti_habits/{$antiHabit->id}")
            ->assertSee('data-controller="calendar-chart"', false)
            ->assertSee('我慢できた');

        $this->actingAs(User::factory()->create())->get("/anti_habits/{$antiHabit->id}")
            ->assertDontSee('data-controller="calendar-chart"', false);
    }

    // ---- create ----

    #[Test]
    public function 未ログインでは作成画面に入れない(): void
    {
        $this->get('/anti_habits/new')->assertRedirect('/users/sign_in');
        $this->post('/anti_habits', $this->validParams())->assertRedirect('/users/sign_in');
        $this->assertSame(0, AntiHabit::count());
    }

    #[Test]
    public function 作成できる(): void
    {
        $user = User::factory()->create();
        Tag::factory()->create(['name' => 'タグ1']);

        $this->actingAs($user)->get('/anti_habits/new')->assertOk();

        $response = $this->actingAs($user)->post('/anti_habits', $this->validParams(['tag_names' => ' タグ1 , タグ2 ']));

        $antiHabit = AntiHabit::sole();
        $response->assertRedirect("/anti_habits/{$antiHabit->id}")->assertSessionHas('notice', '悪習慣を登録しました。');
        $this->assertTrue($antiHabit->user->is($user));
        $this->assertSame(['禁煙', '健康のために禁煙します', true, 30], [$antiHabit->title, $antiHabit->description, $antiHabit->is_public, $antiHabit->goal_days]);
        $this->assertEqualsCanonicalizing(['タグ1', 'タグ2'], $antiHabit->tags->pluck('name')->all());
        $this->assertSame(2, Tag::count());
    }

    #[Test]
    public function 非公開や目標なしでも作成できる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/anti_habits', $this->validParams(['is_public' => '0', 'goal_days' => '']));

        $antiHabit = AntiHabit::sole();
        $this->assertFalse($antiHabit->is_public);
        $this->assertNull($antiHabit->goal_days);
    }

    /** @return array<string, array{0: array<string, string>, 1: string}> */
    public static function invalidParamsProvider(): array
    {
        return [
            'タイトルなし' => [['title' => ''], 'タイトルを入力してください'],
            'タイトル21文字' => [['title' => str_repeat('あ', 21)], 'タイトルは20文字以内で入力してください'],
            '説明81文字' => [['description' => str_repeat('あ', 81)], '説明は80文字以内で入力してください'],
            'タグ16文字' => [['tag_names' => 'ok, '.str_repeat('あ', 16)], 'タグ「'.str_repeat('あ', 16).'」は15文字以内で入力してください'],
            '目標0日' => [['goal_days' => '0'], '目標達成日数は1〜365の範囲で入力してください'],
            '目標366日' => [['goal_days' => '366'], '目標達成日数は1〜365の範囲で入力してください'],
        ];
    }

    #[Test]
    #[DataProvider('invalidParamsProvider')]
    public function 不正な値では作成されない(array $overrides, string $message): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/anti_habits/new')
            ->post('/anti_habits', $this->validParams($overrides))
            ->assertRedirect('/anti_habits/new');

        $this->assertSame(0, AntiHabit::count());
        // リダイレクト先のフォームにエラーメッセージが表示される
        $this->actingAs($user)->get('/anti_habits/new')->assertSee($message);
    }

    // ---- edit / update / destroy ----

    #[Test]
    public function 本人だけが編集できる(): void
    {
        $owner = User::factory()->create();
        $antiHabit = AntiHabit::factory()->for($owner)->create(['title' => '元のタイトル']);
        $antiHabit->syncTagNames('タグA, タグB');

        $this->get("/anti_habits/{$antiHabit->id}/edit")->assertRedirect('/users/sign_in');
        $this->actingAs(User::factory()->create())->get("/anti_habits/{$antiHabit->id}/edit")->assertRedirect('/anti_habits');
        $this->actingAs(User::factory()->create())
            ->patch("/anti_habits/{$antiHabit->id}", $this->validParams(['title' => '乗っ取り']))
            ->assertRedirect('/anti_habits');
        $this->assertSame('元のタイトル', $antiHabit->fresh()->title);

        $this->actingAs($owner)->get("/anti_habits/{$antiHabit->id}/edit")
            ->assertOk()
            ->assertSee('data-tagify-initial-value-value="タグA, タグB"', false);

        $this->actingAs($owner)
            ->patch("/anti_habits/{$antiHabit->id}", $this->validParams(['title' => '新しいタイトル', 'tag_names' => 'タグB, タグC']))
            ->assertRedirect("/anti_habits/{$antiHabit->id}")
            ->assertSessionHas('notice', '悪習慣を更新しました。');

        $antiHabit->refresh();
        $this->assertSame('新しいタイトル', $antiHabit->title);
        $this->assertEqualsCanonicalizing(['タグB', 'タグC'], $antiHabit->tags->pluck('name')->all());

        $this->actingAs($owner)
            ->patch("/anti_habits/{$antiHabit->id}", $this->validParams(['title' => '']))
            ->assertSessionHasErrors('title');
    }

    #[Test]
    public function 本人だけが削除できる(): void
    {
        $owner = User::factory()->create();
        $antiHabit = AntiHabit::factory()->for($owner)->create();

        $this->delete("/anti_habits/{$antiHabit->id}")->assertRedirect('/users/sign_in');
        $this->actingAs(User::factory()->create())->delete("/anti_habits/{$antiHabit->id}")->assertRedirect('/anti_habits');
        $this->assertModelExists($antiHabit);

        $this->actingAs($owner)->delete("/anti_habits/{$antiHabit->id}")
            ->assertStatus(303)
            ->assertRedirect('/anti_habits')
            ->assertSessionHas('notice', '悪習慣を削除しました。');
        $this->assertModelMissing($antiHabit);
    }
}
