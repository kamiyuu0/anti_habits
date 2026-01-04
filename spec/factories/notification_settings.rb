FactoryBot.define do
  factory :notification_setting do
    notification_time { "2025-10-28 22:21:01" }
    notify_on_reaction { false }
    notify_on_comment { false }
    anti_habit { nil }
  end
end
