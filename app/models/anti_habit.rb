class AntiHabit < ApplicationRecord
  belongs_to :user
  has_many :anti_habit_records, dependent: :destroy
  has_many :reactions, dependent: :destroy
  has_many :comments, dependent: :destroy
  has_many :anti_habit_tags, dependent: :destroy
  has_many :tags, through: :anti_habit_tags
  has_one :notification_setting, dependent: :destroy
  has_many :bookmarks, dependent: :destroy

  validates :title, presence: true, length: { maximum: 20 }
  validates :description, length: { maximum: 80 }
  validate :validate_tag_names
  validates :goal_days,
    numericality: {
      only_integer: true,
      greater_than_or_equal_to: 1,
      less_than_or_equal_to: 365,
      allow_nil: true
    }

  scope :tagged_with, ->(name) {
    joins(:tags).where(tags: { name: name })
  }
  scope :publicly_visible, -> { where(is_public: true) }
  scope :recent, -> { order(created_at: :desc) }
  scope :with_tags, -> { includes(:tags) }
  scope :with_associations, -> { includes(:user, :tags, :reactions, :comments) }

  attr_accessor :tag_names

  def self.top_weekly_achievers_with_ranks
    start_of_week = Time.zone.today.beginning_of_week(:monday)
    end_of_week = [ Time.zone.today, start_of_week + 6.days ].min

    weekly_counts = AntiHabitRecord
      .where(recorded_on: start_of_week..end_of_week)
      .group(:anti_habit_id)
      .select(:anti_habit_id, "COUNT(*) AS count")

    candidates = publicly_visible
      .includes(:user)
      .joins("INNER JOIN (#{weekly_counts.to_sql}) weekly ON weekly.anti_habit_id = anti_habits.id")
      .select("anti_habits.*, weekly.count AS weekly_days_count")
      .order("weekly.count DESC")

    grouped = candidates
      .group_by(&:weekly_days_count) # 今週の合計達成日数とAntiHabit配列のハッシュ
      .sort_by { |days, _| -days }

    ranked_data = []
    current_rank = 1
    grouped.each do |days, anti_habits|
      ranked_data << {
        rank: current_rank,
        weekly_days: days,
        anti_habits: anti_habits.sort_by(&:created_at)
      }
      current_rank += anti_habits.size
    end

    first_place_count = ranked_data.first&.dig(:anti_habits)&.size || 0
    if first_place_count >= 3
      ranked_data.take(1)
    else
      first_two_count = ranked_data.take(2).sum { |d| d[:anti_habits].size }
      if first_two_count >= 3
        ranked_data.take(2)
      else
        ranked_data.take(3)
      end
    end
  end

  after_save :save_tags_without_validation
  after_save :check_and_update_goal_achievement

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

  # 目標達成済みかどうかを返す
  def goal_reached?
    return false if goal_days.nil?
    consecutive_days_achieved >= goal_days
  end

  # ヒートマップ用のカレンダーデータを生成
  def calendar_data(days: 90)
    end_date = Time.zone.today
    start_date = end_date - (days - 1).days

    # 記録された日付を一括取得（N+1回避）
    recorded_dates = anti_habit_records
      .where(recorded_on: start_date..end_date)
      .pluck(:recorded_on)
      .to_set

    # rails_charts用のデータ形式
    {
      data: (start_date..end_date).map do |date|
        count = recorded_dates.include?(date) ? 1 : 0
        [ date.to_s, count ]
      end
    }
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

  def check_and_update_goal_achievement
    # 目標日数が変更された場合、達成フラグをリセット
    if saved_change_to_goal_days?
      update_column(:goal_achieved, false)
    end

    return if goal_days.nil?

    # 常に最新の連続達成日数で判定し、フラグを更新
    if goal_reached?
      update_column(:goal_achieved, true)
    else
      update_column(:goal_achieved, false)
    end
  end
end
