class AntiHabitTag < ApplicationRecord
  belongs_to :anti_habit
  belongs_to :tag

  validates :anti_habit_id, uniqueness: { scope: :tag_id }
end
