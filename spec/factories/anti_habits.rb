FactoryBot.define do
  factory :anti_habit do
    association :user
    sequence(:title) { |n| "悪習慣#{n}" }
    description { "これは悪習慣の説明です" }
    is_public { true }
    goal_days { nil }
    goal_achieved { false }

    trait :with_goal do
      goal_days { 30 }
    end

    trait :with_records do
      transient do
        records_count { 5 }
        start_date { Time.zone.today }
      end

      after(:create) do |anti_habit, evaluator|
        evaluator.records_count.times do |i|
          create(:anti_habit_record,
            anti_habit: anti_habit,
            recorded_on: evaluator.start_date - i.days
          )
        end
      end
    end
  end
end
