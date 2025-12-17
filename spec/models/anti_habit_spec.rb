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
      let!(:anti_habit1) { create(:anti_habit, user: user, tags: [tag1]) }
      let!(:anti_habit2) { create(:anti_habit, user: user, tags: [tag2]) }
      let!(:anti_habit3) { create(:anti_habit, user: user, tags: [tag1, tag2]) }

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
end
