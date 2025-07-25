class AntiHabit < ApplicationRecord
  belongs_to :user
  has_many :anti_habit_records, dependent: :destroy

  validates :title, presence: true, length: { maximum: 20 }
  validates :description, length: { maximum: 80 }
end
