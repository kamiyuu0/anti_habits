<?php

namespace Database\Seeders;

use App\Enums\ReactionKind;
use App\Models\AntiHabit;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\User;
use App\Support\AppTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

/** Rails 版 db/seeds.rb と同じ開発用データを作成する */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // タグを作成
        $tagNames = ['健康', '習慣改善', '生活習慣', 'ダイエット', '禁煙', '節約', '勉強', '仕事', '睡眠', '運動'];
        $tags = collect($tagNames)->map(fn ($name) => Tag::firstOrCreate(['name' => $name]));
        $this->command->info("タグを{$tags->count()}件作成しました");

        // テストユーザーを10件作成
        $users = collect(range(1, 10))->map(function ($i) {
            $user = User::firstOrNew(['email' => "user{$i}@example.com"]);
            if (! $user->exists) {
                $user->name = "テストユーザー{$i}";
                $user->setPassword('password123');
                $user->save();
            }

            return $user;
        });
        $this->command->info('テストユーザーを10件作成しました');

        // 各ユーザーにanti_habitsを3件ずつ作成
        $titles = [
            '夜更かし', 'スマホの見過ぎ', '間食', '運動不足', '朝寝坊',
            '無駄遣い', '二度寝', 'ゲームのやりすぎ', 'SNS依存', '夜食',
            'タバコ', 'お酒', 'ギャンブル', '寝る前のスマホ', 'コーヒーの飲みすぎ',
        ];
        $descriptions = [
            'この悪習慣をやめたいです', '健康のために改善したいと思っています',
            '生活習慣を見直すために挑戦中です', '少しずつ改善していきます',
            '目標達成に向けて頑張ります',
        ];
        $goalDaysOptions = [7, 14, 21, 30, 60, 90, 100];

        $antiHabits = collect();
        foreach ($users as $user) {
            for ($i = 0; $i < 3; $i++) {
                $antiHabit = $user->antiHabits()->create([
                    'title' => Arr::random($titles),
                    'description' => Arr::random($descriptions),
                    'is_public' => true,
                    'goal_days' => Arr::random($goalDaysOptions),
                ]);

                // ランダムに1〜3個のタグを関連付け
                $antiHabit->tags()->attach($tags->random(random_int(1, 3))->pluck('id'));

                $antiHabits->push($antiHabit);
            }
        }
        $this->command->info("各ユーザーにanti_habitsを3件ずつ作成しました（合計{$antiHabits->count()}件）");

        // 各anti_habitに、今日から遡って連続する日付でレコードを作成
        $recordCount = 0;
        foreach ($antiHabits as $antiHabit) {
            $days = random_int(0, $antiHabit->goal_days + 20);
            for ($i = 0; $i < $days; $i++) {
                $antiHabit->antiHabitRecords()->create(['recorded_on' => AppTime::today()->subDays($i)->toDateString()]);
                $recordCount++;
            }
        }
        $this->command->info("レコードを{$recordCount}件作成しました");
        $this->command->info('目標達成したanti_habitsは'.AntiHabit::where('goal_achieved', true)->count().'件です');

        $commentTexts = [
            '頑張ってください！応援しています！',
            '私も同じ悩みを抱えています。一緒に頑張りましょう！',
            'すごいですね！継続は力なりです！',
            '無理せず、少しずつ改善していきましょう。',
            '参考になります。私も挑戦してみます！',
            'その調子です！続けることが大切ですね。',
            '同じ目標を持つ仲間として応援しています。',
            '素晴らしい取り組みですね！',
            '一歩ずつ前進していきましょう！',
            'お互い頑張りましょう！',
        ];

        // 各anti_habitにコメントとリアクションを追加（自分以外のユーザーから）
        $commentCount = 0;
        $reactionCount = 0;
        foreach ($antiHabits as $antiHabit) {
            $otherUsers = $users->reject(fn ($u) => $u->id === $antiHabit->user_id);

            for ($i = 0, $n = random_int(2, 5); $i < $n; $i++) {
                $comment = new Comment(['body' => Arr::random($commentTexts)]);
                $comment->antiHabit()->associate($antiHabit);
                $comment->user()->associate($otherUsers->random());
                $comment->save();
                $commentCount++;
            }

            for ($i = 0, $n = random_int(3, 7); $i < $n; $i++) {
                $reaction = $otherUsers->random()->reaction($antiHabit, Arr::random(ReactionKind::cases()));
                if ($reaction->wasRecentlyCreated) {
                    $reactionCount++;
                }
            }
        }
        $this->command->info("コメントを{$commentCount}件作成しました");
        $this->command->info("リアクションを{$reactionCount}件作成しました");

        // 各ユーザーが自分以外のanti_habitsをランダムに2〜5件ブックマーク
        $bookmarkCount = 0;
        foreach ($users as $user) {
            $others = $antiHabits->reject(fn ($a) => $a->user_id === $user->id);
            for ($i = 0, $n = random_int(2, 5); $i < $n; $i++) {
                if ($user->bookmark($others->random())->wasRecentlyCreated) {
                    $bookmarkCount++;
                }
            }
        }
        $this->command->info("ブックマークを{$bookmarkCount}件作成しました");
    }
}
