FactoryBot.define do
  factory :anti_habit_record do
    association :anti_habit
    recorded_on { Time.zone.today }
  end
end
