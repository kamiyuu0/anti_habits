class AntiHabit < ApplicationRecord
  belongs_to :user

  validates :title, presence: true, length: { maximum: 20 }
  validates :description, length: { maximum: 80 }
end
