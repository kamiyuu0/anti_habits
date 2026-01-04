FactoryBot.define do
  factory :reaction do
    association :anti_habit
    association :user
    reaction_kind { :watching }
  end
end
