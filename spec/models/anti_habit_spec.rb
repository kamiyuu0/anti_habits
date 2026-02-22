require 'rails_helper'

RSpec.describe AntiHabit, type: :model do
  describe 'associations' do
    it { should belong_to(:user) }
    it { should have_many(:anti_habit_records).dependent(:destroy) }
    it { should have_many(:reactions).dependent(:destroy) }
    it { should have_many(:comments).dependent(:destroy) }
    it { should have_many(:anti_habit_tags).dependent(:destroy) }
    it { should have_many(:tags).through(:anti_habit_tags) }
    it { should have_one(:notification_setting).dependent(:destroy) }
    it { should have_many(:bookmarks).dependent(:destroy) }
  end

  describe 'validations' do
    it { should validate_presence_of(:title) }
    it { should validate_length_of(:title).is_at_most(20) }
    it { should validate_length_of(:description).is_at_most(80) }
    it do
      should validate_numericality_of(:goal_days)
        .only_integer
        .is_greater_than_or_equal_to(1)
        .is_less_than_or_equal_to(365)
        .allow_nil
    end

    describe 'tag_names validation' do
      let(:user) { create(:user) }
      let(:anti_habit) { build(:anti_habit, user: user) }

      context 'タグ名が15文字以内の場合' do
        it '有効である' do
          anti_habit.tag_names = '短いタグ,test'
          expect(anti_habit).to be_valid
        end
      end

      context 'タグ名が15文字を超える場合' do
        it '無効である' do
          anti_habit.tag_names = 'あ' * 16
          expect(anti_habit).to be_invalid
          expect(anti_habit.errors[:tag_names]).to include("「#{'あ' * 16}」は15文字以内で入力してください")
        end
      end

      context '複数のタグで一部が15文字を超える場合' do
        it '無効である' do
          long_tag = 'あ' * 16
          anti_habit.tag_names = "短いタグ,#{long_tag}"
          expect(anti_habit).to be_invalid
          expect(anti_habit.errors[:tag_names]).to include("「#{long_tag}」は15文字以内で入力してください")
        end
      end
    end
  end

  describe 'scopes' do
    describe '.tagged_with' do
      let(:user) { create(:user) }
      let(:tag1) { Tag.create!(name: 'タグ1') }
      let(:tag2) { Tag.create!(name: 'タグ2') }
      let!(:anti_habit1) { create(:anti_habit, user: user, tags: [ tag1 ]) }
      let!(:anti_habit2) { create(:anti_habit, user: user, tags: [ tag2 ]) }
      let!(:anti_habit3) { create(:anti_habit, user: user, tags: [ tag1, tag2 ]) }

      it '指定したタグを持つAntiHabitを取得する' do
        result = AntiHabit.tagged_with('タグ1')
        expect(result).to include(anti_habit1, anti_habit3)
        expect(result).not_to include(anti_habit2)
      end

      it '存在しないタグでは空を返す' do
        result = AntiHabit.tagged_with('存在しないタグ')
        expect(result).to be_empty
      end
    end

    describe '.publicly_visible' do
      let(:user) { create(:user) }
      let!(:public_anti_habit1) { create(:anti_habit, user: user, is_public: true) }
      let!(:public_anti_habit2) { create(:anti_habit, user: user, is_public: true) }
      let!(:private_anti_habit) { create(:anti_habit, user: user, is_public: false) }

      it '公開されているAntiHabitのみを取得する' do
        result = AntiHabit.publicly_visible
        expect(result).to include(public_anti_habit1, public_anti_habit2)
        expect(result).not_to include(private_anti_habit)
      end
    end
  end

  describe '#today_record' do
    let(:user) { create(:user) }
    let(:anti_habit) { create(:anti_habit, user: user) }

    context '今日の記録がある場合' do
      let!(:today_record) { create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.today) }

      it '今日の記録を返す' do
        expect(anti_habit.today_record).to eq(today_record)
      end
    end

    context '今日の記録がない場合' do
      let!(:yesterday_record) { create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.yesterday) }

      it 'nilを返す' do
        expect(anti_habit.today_record).to be_nil
      end
    end

    context '記録が全くない場合' do
      it 'nilを返す' do
        expect(anti_habit.today_record).to be_nil
      end
    end
  end

  describe '#goal_reached?' do
    let(:user) { create(:user) }
    let(:anti_habit) { create(:anti_habit, user: user, goal_days: 5) }

    context 'goal_daysがnilの場合' do
      it 'falseを返す' do
        anti_habit.goal_days = nil
        expect(anti_habit.goal_reached?).to be false
      end
    end

    context '連続達成日数がgoal_daysより少ない場合' do
      it 'falseを返す' do
        travel_to Time.zone.today do
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.today)
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.yesterday)
          # 連続2日、goal_daysは5日
          expect(anti_habit.goal_reached?).to be false
        end
      end
    end

    context '連続達成日数がgoal_daysと等しい場合' do
      it 'trueを返す' do
        travel_to Time.zone.today do
          5.times do |i|
            create(:anti_habit_record, anti_habit: anti_habit, recorded_on: i.days.ago)
          end
          # 連続5日、goal_daysは5日
          expect(anti_habit.goal_reached?).to be true
        end
      end
    end

    context '連続達成日数がgoal_daysより多い場合' do
      it 'trueを返す' do
        travel_to Time.zone.today do
          7.times do |i|
            create(:anti_habit_record, anti_habit: anti_habit, recorded_on: i.days.ago)
          end
          # 連続7日、goal_daysは5日
          expect(anti_habit.goal_reached?).to be true
        end
      end
    end
  end

  describe '#calendar_data' do
    let(:user) { create(:user) }
    let(:anti_habit) { create(:anti_habit, user: user) }

    context 'デフォルト（90日間）の場合' do
      it '90日分のデータを生成する' do
        travel_to Time.zone.today do
          result = anti_habit.calendar_data
          expect(result[:data].size).to eq(90)
        end
      end

      it '正しい日付範囲のデータを含む' do
        travel_to Time.zone.today do
          result = anti_habit.calendar_data
          dates = result[:data].map { |d| d[0] }
          expect(dates.first).to eq((Time.zone.today - 89.days).to_s)
          expect(dates.last).to eq(Time.zone.today.to_s)
        end
      end
    end

    context 'カスタム日数（30日間）の場合' do
      it '30日分のデータを生成する' do
        travel_to Time.zone.today do
          result = anti_habit.calendar_data(days: 30)
          expect(result[:data].size).to eq(30)
        end
      end
    end

    context '記録がある場合' do
      it '記録がある日は1、ない日は0を返す' do
        travel_to Time.zone.today do
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.today)
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: 2.days.ago)

          result = anti_habit.calendar_data(days: 5)
          data_hash = result[:data].to_h

          expect(data_hash[Time.zone.today.to_s]).to eq(1)
          expect(data_hash[2.days.ago.to_date.to_s]).to eq(1)
          expect(data_hash[1.day.ago.to_date.to_s]).to eq(0)
        end
      end
    end
  end

  describe '#tag_names_as_string' do
    let(:user) { create(:user) }
    let(:anti_habit) { create(:anti_habit, user: user) }

    context 'タグがない場合' do
      it '空文字列を返す' do
        expect(anti_habit.tag_names_as_string).to eq('')
      end
    end

    context 'タグが1つの場合' do
      it 'そのタグ名を返す' do
        tag = Tag.create!(name: 'テストタグ')
        anti_habit.tags << tag
        expect(anti_habit.tag_names_as_string).to eq('テストタグ')
      end
    end

    context 'タグが複数の場合' do
      it 'カンマ区切りの文字列を返す' do
        tag1 = Tag.create!(name: 'タグ1')
        tag2 = Tag.create!(name: 'タグ2')
        tag3 = Tag.create!(name: 'タグ3')
        anti_habit.tags << [ tag1, tag2, tag3 ]
        expect(anti_habit.tag_names_as_string).to eq('タグ1, タグ2, タグ3')
      end
    end
  end

  describe '#consecutive_days_achieved' do
    let(:user) { create(:user) }
    let(:anti_habit) { create(:anti_habit, user: user) }

    context '記録がない場合' do
      it '0を返す' do
        expect(anti_habit.consecutive_days_achieved).to eq(0)
      end
    end

    context '今日のみ記録がある場合' do
      it '1を返す' do
        travel_to Time.zone.today do
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.today)
          expect(anti_habit.consecutive_days_achieved).to eq(1)
        end
      end
    end

    context '昨日のみ記録がある場合' do
      it '1を返す' do
        travel_to Time.zone.today do
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.yesterday)
          expect(anti_habit.consecutive_days_achieved).to eq(1)
        end
      end
    end

    context '今日と昨日に連続記録がある場合' do
      it '2を返す' do
        travel_to Time.zone.today do
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.today)
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.yesterday)
          expect(anti_habit.consecutive_days_achieved).to eq(2)
        end
      end
    end

    context '3日連続で記録がある場合' do
      it '3を返す' do
        travel_to Time.zone.today do
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.today)
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.yesterday)
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: 2.days.ago)
          expect(anti_habit.consecutive_days_achieved).to eq(3)
        end
      end
    end

    context '今日なし、昨日のみ記録がある場合' do
      it '1を返す' do
        travel_to Time.zone.today do
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.yesterday)
          expect(anti_habit.consecutive_days_achieved).to eq(1)
        end
      end
    end

    context '途中で途切れている場合' do
      it '途切れる前までの日数を返す' do
        travel_to Time.zone.today do
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.today)
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.yesterday)
          # 2日前は記録なし（途切れている）
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: 3.days.ago)
          expect(anti_habit.consecutive_days_achieved).to eq(2)
        end
      end
    end

    context '一昨日まで連続、昨日なし、今日ありの場合' do
      it '1を返す（連続が途切れている）' do
        travel_to Time.zone.today do
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.today)
          # 昨日なし
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: 2.days.ago)
          expect(anti_habit.consecutive_days_achieved).to eq(1)
        end
      end
    end

    context '過去に長期連続、最近途切れた場合' do
      it '最近の連続日数のみを返す' do
        travel_to Time.zone.today do
          # 最近の連続: 今日と昨日
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.today)
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.yesterday)
          # 2日前なし（途切れている）
          # 過去の長期連続
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: 3.days.ago)
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: 4.days.ago)
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: 5.days.ago)
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: 6.days.ago)
          expect(anti_habit.consecutive_days_achieved).to eq(2)
        end
      end
    end
  end

  describe '.top_weekly_achievers_with_ranks' do
    let(:user1) { create(:user) }
    let(:user2) { create(:user) }
    let(:user3) { create(:user) }
    let(:user4) { create(:user) }
    let(:user5) { create(:user) }

    # 日曜日に固定（月曜始まりの週が月〜日の7日間になる）
    let(:this_sunday) { Date.new(2024, 1, 7) }

    context 'データがない場合' do
      it '空の配列を返す' do
        travel_to this_sunday do
          result = AntiHabit.top_weekly_achievers_with_ranks
          expect(result).to eq([])
        end
      end
    end

    context '今週の達成がない場合' do
      it '結果に含まれない' do
        travel_to this_sunday do
          create(:anti_habit, user: user1, is_public: true)
          result = AntiHabit.top_weekly_achievers_with_ranks
          expect(result).to eq([])
        end
      end
    end

    context '今週のレコードのみカウントされる' do
      it '先週のレコードは除外される' do
        travel_to this_sunday do
          anti_habit = create(:anti_habit, user: user1, is_public: true)
          create(:anti_habit_record, anti_habit: anti_habit, recorded_on: 7.days.ago)
          result = AntiHabit.top_weekly_achievers_with_ranks
          expect(result).to eq([])
        end
      end
    end

    context '非公開のanti_habitは含まれない' do
      it '公開されているもののみを対象とする' do
        travel_to this_sunday do
          public_anti_habit = create(:anti_habit, user: user1, is_public: true)
          create(:anti_habit_record, anti_habit: public_anti_habit, recorded_on: Time.zone.today)

          private_anti_habit = create(:anti_habit, user: user2, is_public: false)
          create(:anti_habit_record, anti_habit: private_anti_habit, recorded_on: Time.zone.today)

          result = AntiHabit.top_weekly_achievers_with_ranks
          expect(result.size).to eq(1)
          expect(result[0][:anti_habits]).to include(public_anti_habit)
          expect(result[0][:anti_habits]).not_to include(private_anti_habit)
        end
      end
    end

    context '1位が3名以上の場合' do
      it '1位のみを表示する' do
        travel_to this_sunday do
          # 1位: 今週5日達成（3名）
          anti_habit1 = create(:anti_habit, user: user1, is_public: true)
          anti_habit2 = create(:anti_habit, user: user2, is_public: true)
          anti_habit3 = create(:anti_habit, user: user3, is_public: true)
          [ anti_habit1, anti_habit2, anti_habit3 ].each do |ah|
            5.times { |i| create(:anti_habit_record, anti_habit: ah, recorded_on: i.days.ago) }
          end

          # 2位: 今週3日達成（1名）
          anti_habit4 = create(:anti_habit, user: user4, is_public: true)
          3.times { |i| create(:anti_habit_record, anti_habit: anti_habit4, recorded_on: i.days.ago) }

          result = AntiHabit.top_weekly_achievers_with_ranks
          expect(result.size).to eq(1)
          expect(result[0][:rank]).to eq(1)
          expect(result[0][:weekly_days]).to eq(5)
          expect(result[0][:anti_habits].size).to eq(3)
        end
      end
    end

    context '1位が3名未満で、1位+2位が3名以上の場合' do
      it '1位と2位を表示する' do
        travel_to this_sunday do
          # 1位: 今週5日達成（2名）
          anti_habit1 = create(:anti_habit, user: user1, is_public: true)
          anti_habit2 = create(:anti_habit, user: user2, is_public: true)
          [ anti_habit1, anti_habit2 ].each do |ah|
            5.times { |i| create(:anti_habit_record, anti_habit: ah, recorded_on: i.days.ago) }
          end

          # 2位: 今週3日達成（2名）
          anti_habit3 = create(:anti_habit, user: user3, is_public: true)
          anti_habit4 = create(:anti_habit, user: user4, is_public: true)
          [ anti_habit3, anti_habit4 ].each do |ah|
            3.times { |i| create(:anti_habit_record, anti_habit: ah, recorded_on: i.days.ago) }
          end

          # 3位: 今週1日達成（1名）
          anti_habit5 = create(:anti_habit, user: user5, is_public: true)
          create(:anti_habit_record, anti_habit: anti_habit5, recorded_on: Time.zone.today)

          result = AntiHabit.top_weekly_achievers_with_ranks
          expect(result.size).to eq(2)
          expect(result[0][:rank]).to eq(1)
          expect(result[0][:weekly_days]).to eq(5)
          expect(result[0][:anti_habits].size).to eq(2)
          expect(result[1][:rank]).to eq(3)
          expect(result[1][:weekly_days]).to eq(3)
          expect(result[1][:anti_habits].size).to eq(2)
        end
      end
    end

    context '1位が3名未満、1位+2位も3名未満の場合' do
      it '上位3つの順位グループを表示する' do
        travel_to this_sunday do
          # 1位: 今週5日達成（1名）
          anti_habit1 = create(:anti_habit, user: user1, is_public: true)
          5.times { |i| create(:anti_habit_record, anti_habit: anti_habit1, recorded_on: i.days.ago) }

          # 2位: 今週3日達成（1名）
          anti_habit2 = create(:anti_habit, user: user2, is_public: true)
          3.times { |i| create(:anti_habit_record, anti_habit: anti_habit2, recorded_on: i.days.ago) }

          # 3位: 今週1日達成（2名）
          anti_habit3 = create(:anti_habit, user: user3, is_public: true)
          create(:anti_habit_record, anti_habit: anti_habit3, recorded_on: Time.zone.today)

          anti_habit4 = create(:anti_habit, user: user4, is_public: true)
          create(:anti_habit_record, anti_habit: anti_habit4, recorded_on: Time.zone.today)

          result = AntiHabit.top_weekly_achievers_with_ranks
          expect(result.size).to eq(3)
          expect(result[0][:rank]).to eq(1)
          expect(result[0][:weekly_days]).to eq(5)
          expect(result[1][:rank]).to eq(2)
          expect(result[1][:weekly_days]).to eq(3)
          expect(result[2][:rank]).to eq(3)
          expect(result[2][:weekly_days]).to eq(1)
        end
      end
    end

    context '同じ週間達成日数の場合' do
      it 'created_atでソートされる' do
        travel_to this_sunday do
          anti_habit1 = create(:anti_habit, user: user1, is_public: true, created_at: 3.days.ago)
          anti_habit2 = create(:anti_habit, user: user2, is_public: true, created_at: 2.days.ago)
          anti_habit3 = create(:anti_habit, user: user3, is_public: true, created_at: 1.day.ago)

          [ anti_habit1, anti_habit2, anti_habit3 ].each do |ah|
            5.times { |i| create(:anti_habit_record, anti_habit: ah, recorded_on: i.days.ago) }
          end

          result = AntiHabit.top_weekly_achievers_with_ranks
          expect(result.size).to eq(1)
          expect(result[0][:rank]).to eq(1)
          expect(result[0][:anti_habits][0]).to eq(anti_habit1)
          expect(result[0][:anti_habits][1]).to eq(anti_habit2)
          expect(result[0][:anti_habits][2]).to eq(anti_habit3)
        end
      end
    end

    context 'rankの計算が正しい' do
      it '次の順位は前の順位+人数になる' do
        travel_to this_sunday do
          # 1位: 今週5日達成（2名）
          anti_habit1 = create(:anti_habit, user: user1, is_public: true)
          anti_habit2 = create(:anti_habit, user: user2, is_public: true)
          [ anti_habit1, anti_habit2 ].each do |ah|
            5.times { |i| create(:anti_habit_record, anti_habit: ah, recorded_on: i.days.ago) }
          end

          # 2位（実際のrankは3）: 今週3日達成（1名）
          anti_habit3 = create(:anti_habit, user: user3, is_public: true)
          3.times { |i| create(:anti_habit_record, anti_habit: anti_habit3, recorded_on: i.days.ago) }

          result = AntiHabit.top_weekly_achievers_with_ranks
          expect(result[0][:rank]).to eq(1)
          expect(result[1][:rank]).to eq(3) # 1位が2名いるので、次は3位
        end
      end
    end
  end

  describe 'callbacks' do
    describe 'save_tags_without_validation' do
      let(:user) { create(:user) }

      context 'tag_namesが設定されている場合' do
        it '保存後にタグが関連付けられる' do
          anti_habit = create(:anti_habit, user: user)
          anti_habit.tag_names = 'タグ1,タグ2,タグ3'
          anti_habit.save

          expect(anti_habit.tags.count).to eq(3)
          expect(anti_habit.tags.pluck(:name)).to contain_exactly('タグ1', 'タグ2', 'タグ3')
        end
      end

      context 'タグが既に存在する場合' do
        it '新規作成せずに既存のタグを使用する' do
          existing_tag = Tag.create!(name: '既存タグ')
          initial_tag_count = Tag.count

          anti_habit = create(:anti_habit, user: user)
          anti_habit.tag_names = '既存タグ,新規タグ'
          anti_habit.save

          expect(Tag.count).to eq(initial_tag_count + 1) # 新規タグのみ追加
          expect(anti_habit.tags).to include(existing_tag)
        end
      end

      context '既存のタグ関連がある場合' do
        it '更新時に既存のタグ関連を削除して新しいタグを設定する' do
          tag1 = Tag.create!(name: 'タグ1')
          anti_habit = create(:anti_habit, user: user, tags: [ tag1 ])

          anti_habit.tag_names = 'タグ2,タグ3'
          anti_habit.save

          expect(anti_habit.tags.count).to eq(2)
          expect(anti_habit.tags.pluck(:name)).to contain_exactly('タグ2', 'タグ3')
          expect(anti_habit.tags).not_to include(tag1)
        end
      end

      context 'tag_namesが空白を含む場合' do
        it '空白をトリムして保存する' do
          anti_habit = create(:anti_habit, user: user)
          anti_habit.tag_names = ' タグ1 , タグ2 , タグ3 '
          anti_habit.save

          expect(anti_habit.tags.pluck(:name)).to contain_exactly('タグ1', 'タグ2', 'タグ3')
        end
      end

      context 'tag_namesがnilの場合' do
        it 'タグの関連付けを変更しない' do
          tag = Tag.create!(name: 'タグ1')
          anti_habit = create(:anti_habit, user: user, tags: [ tag ])
          anti_habit.title = '新しいタイトル'
          anti_habit.save

          expect(anti_habit.tags).to include(tag)
        end
      end
    end

    describe 'check_and_update_goal_achievement' do
      let(:user) { create(:user) }

      context 'goal_daysが変更された場合' do
        it 'goal_achievedがfalseにリセットされる' do
          travel_to Time.zone.today do
            anti_habit = create(:anti_habit, user: user, goal_days: 3)
            3.times { |i| create(:anti_habit_record, anti_habit: anti_habit, recorded_on: i.days.ago) }
            anti_habit.reload
            expect(anti_habit.goal_achieved).to be true

            # goal_daysを変更
            anti_habit.update(goal_days: 5)
            expect(anti_habit.goal_achieved).to be false
          end
        end
      end

      context '連続達成日数が目標に達している場合' do
        it 'goal_achievedがtrueになる' do
          travel_to Time.zone.today do
            anti_habit = create(:anti_habit, user: user, goal_days: 3)
            3.times { |i| create(:anti_habit_record, anti_habit: anti_habit, recorded_on: i.days.ago) }

            anti_habit.save
            anti_habit.reload
            expect(anti_habit.goal_achieved).to be true
          end
        end
      end

      context '連続達成日数が目標に達していない場合' do
        it 'goal_achievedがfalseになる' do
          travel_to Time.zone.today do
            anti_habit = create(:anti_habit, user: user, goal_days: 5)
            2.times { |i| create(:anti_habit_record, anti_habit: anti_habit, recorded_on: i.days.ago) }

            anti_habit.save
            anti_habit.reload
            expect(anti_habit.goal_achieved).to be false
          end
        end
      end

      context 'goal_daysがnilの場合' do
        it 'goal_achievedの更新をスキップする' do
          anti_habit = create(:anti_habit, user: user, goal_days: nil)
          anti_habit.reload
          # goal_daysがnilの場合は更新処理が行われない
          expect(anti_habit.goal_achieved).to be false
        end
      end

      context '目標達成後に記録を追加した場合' do
        it 'goal_achievedがtrueのままになる' do
          travel_to Time.zone.today do
            anti_habit = create(:anti_habit, user: user, goal_days: 3)
            5.times { |i| create(:anti_habit_record, anti_habit: anti_habit, recorded_on: i.days.ago) }

            anti_habit.save
            anti_habit.reload
            expect(anti_habit.goal_achieved).to be true
          end
        end
      end

      context '目標達成後に連続が途切れた場合' do
        it 'goal_achievedがfalseになる' do
          travel_to Time.zone.today do
            anti_habit = create(:anti_habit, user: user, goal_days: 3)
            # 今日と昨日のみ記録（連続2日）
            create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.today)
            create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.yesterday)

            anti_habit.save
            anti_habit.reload
            expect(anti_habit.goal_achieved).to be false
          end
        end
      end
    end
  end
end
