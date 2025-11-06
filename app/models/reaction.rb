class Reaction < ApplicationRecord
  belongs_to :anti_habit
  belongs_to :user

  validates :user_id, uniqueness: { scope: [:anti_habit_id, :reaction_kind] }
  enum reaction_kind: { watching: 0, fighting: 1, zen: 2 }
end
