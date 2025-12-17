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
end
