class AntiHabit < ApplicationRecord
  belongs_to :user
  has_many :anti_habit_records, dependent: :destroy

  validates :title, presence: true, length: { maximum: 20 }
  validates :description, length: { maximum: 80 }

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
end
