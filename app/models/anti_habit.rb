class AntiHabit < ApplicationRecord
  belongs_to :user
  has_many :anti_habit_records, dependent: :destroy
  has_many :reactions, dependent: :destroy
  has_many :comments, dependent: :destroy
  has_many :anti_habit_tags, dependent: :destroy
  has_many :tags, through: :anti_habit_tags
  has_one :notification_setting, dependent: :destroy

  validates :title, presence: true, length: { maximum: 20 }
  validates :description, length: { maximum: 80 }
  validate :validate_tag_names

  scope :tagged_with, ->(name) {
    joins(:tags).where(tags: { name: name })
  }
  scope :publicly_visible, -> { where(is_public: true) }

  attr_accessor :tag_names

  after_save :save_tags_without_validation

  def today_record
    anti_habit_records.find_by(recorded_on: Time.zone.today)
  end

  def consecutive_days_achieved
    # 今日または昨日から開始
    start_date = today_record ? Time.zone.today : Time.zone.yesterday

    # 連続する記録を一括取得
    records = anti_habit_records
      .where("recorded_on <= ?", start_date)
      .order(recorded_on: :desc)
      .pluck(:recorded_on)

    count = 0
    expected_date = start_date # expected_dateは説明変数として使用

    records.each do |recorded_on|
      if recorded_on == expected_date
        count += 1
        expected_date -= 1.day
      else
        break
      end
    end

    count
  end

  def tag_names_as_string
    tags.pluck(:name).join(", ")
  end

  private

  def validate_tag_names
    return unless tag_names.present?

    names = tag_names.split(",").map(&:strip).reject(&:blank?)

    names.each do |name|
      if name.length > 15
        errors.add(:tag_names, "「#{name}」は15文字以内で入力してください")
      end
    end
  end

  def save_tags_without_validation
    return unless tag_names

    names = tag_names.split(",").map(&:strip).reject(&:blank?)

    # 既存のタグ関連を削除
    # TODO: 更新時、すでにあるタグは削除しないようにする
    anti_habit_tags.destroy_all

    # 新しいタグを作成・関連付け
    tags_to_add = Tag.find_or_create_by_names(names)
    self.tags = tags_to_add
  end

  def self.ransackable_attributes(auth_object = nil)
    [ "title", "description" ]
  end

  def self.ransackable_associations(auth_object = nil)
    [ "tags" ]
  end
end
