FactoryBot.define do
  factory :bookmark do
    association :user
    association :anti_habit
  end
end
