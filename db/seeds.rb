# This file should ensure the existence of records required to run the application in every environment (production,
# development, test). The code here should be idempotent so that it can be executed at any point in every environment.
# The data can then be loaded with the bin/rails db:seed command (or created alongside the database with db:setup).
#
# Example:
#
#   ["Action", "Comedy", "Drama", "Horror"].each do |genre_name|
#     MovieGenre.find_or_create_by!(name: genre_name)
#   end

# タグを作成
tag_names = [ "健康", "習慣改善", "生活習慣", "ダイエット", "禁煙", "節約", "勉強", "仕事", "睡眠", "運動" ]
tags = tag_names.map do |name|
  Tag.find_or_create_by!(name: name)
end
puts "タグを#{tags.count}件作成しました"

# テストユーザーを10件作成
users = []
10.times do |i|
  user = User.find_or_create_by!(email: "user#{i + 1}@example.com") do |u|
    u.name = "テストユーザー#{i + 1}"
    u.password = "password123"
    u.password_confirmation = "password123"
  end
  users << user
end
puts "テストユーザーを10件作成しました"

# 各ユーザーにanti_habitsを3件ずつ作成
anti_habit_titles = [
  "夜更かし", "スマホの見過ぎ", "間食", "運動不足", "朝寝坊",
  "無駄遣い", "二度寝", "ゲームのやりすぎ", "SNS依存", "夜食",
  "タバコ", "お酒", "ギャンブル", "寝る前のスマホ", "コーヒーの飲みすぎ"
]

anti_habit_descriptions = [
  "この悪習慣をやめたいです", "健康のために改善したいと思っています",
  "生活習慣を見直すために挑戦中です", "少しずつ改善していきます",
  "目標達成に向けて頑張ります"
]

anti_habits = []
goal_days_options = [ 7, 14, 21, 30, 60, 90, 100 ]

users.each do |user|
  3.times do |i|
    anti_habit = AntiHabit.create!(
      user: user,
      title: anti_habit_titles.sample,
      description: anti_habit_descriptions.sample,
      is_public: true,
      goal_days: goal_days_options.sample
    )

    # ランダムに1〜3個のタグを関連付け
    selected_tags = tags.sample(rand(1..3))
    anti_habit.tags << selected_tags

    anti_habits << anti_habit
  end
end

puts "各ユーザーにanti_habitsを3件ずつ作成しました（合計#{users.count * 3}件）"

# 各anti_habitにランダムにレコードを追加
record_count = 0

anti_habits.each do |anti_habit|
  # ランダムな日数分のレコードを作成（0〜目標日数+20日の範囲）
  days_to_record = rand(0..(anti_habit.goal_days + 20))

  # 今日から遡って連続する日付でレコードを作成
  days_to_record.times do |i|
    recorded_on = Time.zone.today - i.days
    AntiHabitRecord.create!(
      anti_habit: anti_habit,
      recorded_on: recorded_on
    )
    record_count += 1
  end
end

puts "レコードを#{record_count}件作成しました"

# 目標達成したanti_habitsの数を確認
achieved_count = AntiHabit.where(goal_achieved: true).count
puts "目標達成したanti_habitsは#{achieved_count}件です"

# コメントのサンプルテキスト
comment_texts = [
  "頑張ってください！応援しています！",
  "私も同じ悩みを抱えています。一緒に頑張りましょう！",
  "すごいですね！継続は力なりです！",
  "無理せず、少しずつ改善していきましょう。",
  "参考になります。私も挑戦してみます！",
  "その調子です！続けることが大切ですね。",
  "同じ目標を持つ仲間として応援しています。",
  "素晴らしい取り組みですね！",
  "一歩ずつ前進していきましょう！",
  "お互い頑張りましょう！"
]

# 各anti_habitにコメントとリアクションを追加
comment_count = 0
reaction_count = 0

anti_habits.each do |anti_habit|
  # 各anti_habitに2〜5件のコメントを作成（自分以外のユーザーから）
  other_users = users.reject { |u| u.id == anti_habit.user_id }
  rand(2..5).times do
    commenter = other_users.sample
    Comment.create!(
      anti_habit: anti_habit,
      user: commenter,
      body: comment_texts.sample
    )
    comment_count += 1
  end

  # 各anti_habitに3〜7件のリアクションを作成（自分以外のユーザーから）
  reaction_kinds = [ :watching, :fighting, :zen, :fire ]
  rand(3..7).times do
    reactor = other_users.sample
    reaction_kind = reaction_kinds.sample

    # 同じユーザーが同じantihabitに同じ種類のリアクションを複数つけないようにチェック
    unless Reaction.exists?(user: reactor, anti_habit: anti_habit, reaction_kind: reaction_kind)
      Reaction.create!(
        anti_habit: anti_habit,
        user: reactor,
        reaction_kind: reaction_kind
      )
      reaction_count += 1
    end
  end
end

puts "コメントを#{comment_count}件作成しました"
puts "リアクションを#{reaction_count}件作成しました"

# 各ユーザーがブックマークを作成（自分以外のanti_habitsをランダムに2〜5件）
bookmark_count = 0

users.each do |user|
  # 自分以外のanti_habitsを取得
  other_anti_habits = anti_habits.reject { |ah| ah.user_id == user.id }

  # ランダムに2〜5件をブックマーク
  rand(2..5).times do
    anti_habit = other_anti_habits.sample

    # 既にブックマークしていないかチェック
    unless Bookmark.exists?(user: user, anti_habit: anti_habit)
      Bookmark.create!(
        user: user,
        anti_habit: anti_habit
      )
      bookmark_count += 1
    end
  end
end

puts "ブックマークを#{bookmark_count}件作成しました"
