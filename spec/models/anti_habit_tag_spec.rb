require 'rails_helper'

RSpec.describe AntiHabitTag, type: :model do
  describe 'associations' do
    it { should belong_to(:anti_habit) }
    it { should belong_to(:tag) }
  end

  describe 'validations' do
    context '同一anti_habit・tagの場合' do
      it '無効である' do
        anti_habit_tag = create(:anti_habit_tag)
        duplicate = build(:anti_habit_tag, anti_habit: anti_habit_tag.anti_habit, tag: anti_habit_tag.tag)
        expect(duplicate).to be_invalid
      end
    end

    context '異なるtagの場合' do
      it '有効である' do
        anti_habit_tag = create(:anti_habit_tag)
        other = build(:anti_habit_tag, anti_habit: anti_habit_tag.anti_habit, tag: create(:tag))
        expect(other).to be_valid
      end
    end

    context '異なるanti_habitの場合' do
      it '有効である' do
        anti_habit_tag = create(:anti_habit_tag)
        other = build(:anti_habit_tag, anti_habit: create(:anti_habit), tag: anti_habit_tag.tag)
        expect(other).to be_valid
      end
    end
  end
end
