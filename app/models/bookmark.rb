class Bookmark < ApplicationRecord
  belongs_to :user
  belongs_to :anti_habit

  validates :user_id, uniqueness: { scope: :anti_habit_id }

  validate :cannot_bookmark_own_anti_habit
  validate :anti_habit_must_be_public

  private

  def cannot_bookmark_own_anti_habit
    if user_id == anti_habit&.user_id
      errors.add(:base, "自分の悪習慣はブックマークできません")
    end
  end

  def anti_habit_must_be_public
    if anti_habit && !anti_habit.is_public?
      errors.add(:base, "非公開の悪習慣はブックマークできません")
    end
  end
end
