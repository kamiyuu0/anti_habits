class AntiHabitRecord < ApplicationRecord
  belongs_to :anti_habit

  validates :recorded_on, presence: true
  validates :anti_habit_id, uniqueness: { scope: :recorded_on, message: "その日はすでに記録済みです" }

  after_commit :check_parent_goal_achievement, on: [ :create, :destroy ]

  private

  def check_parent_goal_achievement
    anti_habit.send(:check_and_update_goal_achievement)
  end
end
