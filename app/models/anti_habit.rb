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

  attr_accessor :tag_names

  def self.top_consecutive_achievers_with_ranks
    # 公開されている悪習慣で連続達成日数が1以上のものを取得
    candidates = publicly_visible
      .includes(:user)
      .select { |anti_habit| anti_habit.consecutive_days_achieved > 0 }

    # 連続達成日数でグループ化し、降順でソート
    grouped = candidates
      .group_by(&:consecutive_days_achieved) # 連続達成日数とAntiHabit配列のハッシュ
      .sort_by { |days, _| -days }

    # 順位付きデータを作成
    ranked_data = []
    current_rank = 1

    grouped.each do |days, anti_habits|
      ranked_data << {
        rank: current_rank,
        consecutive_days: days,
        anti_habits: anti_habits.sort_by { |ah| ah.created_at }
      }
      current_rank += anti_habits.size
    end

    # フィルタリングロジック
    # 1位の人数をチェック
    first_place_count = ranked_data.first&.dig(:anti_habits)&.size || 0

    if first_place_count >= 3
      # 1位が3名以上なら1位のみ表示（インデックス0のみ）
      ranked_data.take(1)
    else
      # 1位が3名未満の場合、1位+2位の合計をチェック
      first_two_count = ranked_data.take(2).sum { |data| data[:anti_habits].size }

      if first_two_count >= 3
        # 1位+2位が3名以上なら上位2つの順位グループを表示（インデックス0-1）
        ranked_data.take(2)
      else
        # それ以外は上位3つの順位グループを表示（インデックス0-2）
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
