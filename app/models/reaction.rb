class Reaction < ApplicationRecord
  belongs_to :anti_habit
  belongs_to :user

  enum reaction_kind: { watching: 0 }
end
