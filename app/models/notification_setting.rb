class NotificationSetting < ApplicationRecord
  belongs_to :anti_habit
  validates :notification_time, presence: true
  validates :notification_enabled, inclusion: { in: [ true, false ] }
  validates :notify_on_reaction, inclusion: { in: [ true, false ] }
  validates :notify_on_comment, inclusion: { in: [ true, false ] }
end
