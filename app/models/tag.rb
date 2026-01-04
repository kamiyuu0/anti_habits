class Tag < ApplicationRecord
  has_many :anti_habit_tags, dependent: :destroy
  has_many :anti_habits, through: :anti_habit_tags

  validates :name, presence: true, uniqueness: true, length: { maximum: 15 }

  # namesのtagオブジェクトをdbから探す、なければ作成して返すメソッド。
  # 返り値はtagの配列
  def self.find_or_create_by_names(names)
    names.map { |name| find_or_create_by(name: name.strip) if name.strip.present? }.compact
  end

  private

  def self.ransackable_attributes(auth_object = nil)
    [ "name" ]
  end
end
