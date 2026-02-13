FactoryBot.define do
  factory :comment do
    association :anti_habit
    association :user
    body { "テストコメント" }
  end
end
