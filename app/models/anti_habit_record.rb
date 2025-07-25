class AntiHabitRecord < ApplicationRecord
  belongs_to :anti_habit

  validates :recorded_on, presence: true
  validates :anti_habit_id, uniqueness: { scope: :recorded_on, message: "その日はすでに記録済みです" }

end
