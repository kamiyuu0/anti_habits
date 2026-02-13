require 'rails_helper'

RSpec.describe AntiHabitRecord, type: :model do
  describe 'associations' do
    it { should belong_to(:anti_habit) }
  end

  describe 'validations' do
    it { should validate_presence_of(:recorded_on) }

    context '同日・同anti_habitの場合' do
      it '無効でエラーメッセージが表示される' do
        record = create(:anti_habit_record, recorded_on: Time.zone.today)
        duplicate = build(:anti_habit_record, anti_habit: record.anti_habit, recorded_on: Time.zone.today)
        expect(duplicate).to be_invalid
        expect(duplicate.errors[:anti_habit_id]).to include('その日はすでに記録済みです')
      end
    end

    context '異なる日の場合' do
      it '有効である' do
        record = create(:anti_habit_record, recorded_on: Time.zone.today)
        other_record = build(:anti_habit_record, anti_habit: record.anti_habit, recorded_on: Time.zone.yesterday)
        expect(other_record).to be_valid
      end
    end

    context '異なるanti_habitの場合' do
      it '有効である' do
        create(:anti_habit_record, recorded_on: Time.zone.today)
        other_record = build(:anti_habit_record, recorded_on: Time.zone.today)
        expect(other_record).to be_valid
      end
    end
  end

  describe 'callbacks' do
    let(:user) { create(:user) }

    context '記録作成で目標達成する場合' do
      it 'goal_achievedがtrueになる' do
        travel_to Time.zone.today do
          anti_habit = create(:anti_habit, user: user, goal_days: 3)
          3.times { |i| create(:anti_habit_record, anti_habit: anti_habit, recorded_on: i.days.ago) }
          expect(anti_habit.reload.goal_achieved).to be true
        end
      end
    end

    context '記録作成で目標未達成の場合' do
      it 'goal_achievedがfalseのままである' do
        travel_to Time.zone.today do
          anti_habit = create(:anti_habit, user: user, goal_days: 5)
          2.times { |i| create(:anti_habit_record, anti_habit: anti_habit, recorded_on: i.days.ago) }
          expect(anti_habit.reload.goal_achieved).to be false
        end
      end
    end

    context '記録削除で目標を下回る場合' do
      it 'goal_achievedがfalseになる' do
        travel_to Time.zone.today do
          anti_habit = create(:anti_habit, user: user, goal_days: 3)
          records = 3.times.map { |i| create(:anti_habit_record, anti_habit: anti_habit, recorded_on: i.days.ago) }
          expect(anti_habit.reload.goal_achieved).to be true

          records.first.destroy
          expect(anti_habit.reload.goal_achieved).to be false
        end
      end
    end
  end
end
