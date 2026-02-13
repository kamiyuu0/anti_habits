FactoryBot.define do
  factory :anti_habit_tag do
    association :anti_habit
    association :tag
  end
end
