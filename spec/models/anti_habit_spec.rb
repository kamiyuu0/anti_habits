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
end
