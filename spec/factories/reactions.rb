FactoryBot.define do
  factory :reaction do
    reaction_kind { 1 }
    anti_habit { nil }
    user { nil }
  end
end
