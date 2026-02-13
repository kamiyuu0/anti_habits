require 'rails_helper'

RSpec.describe Reaction, type: :model do
  describe 'associations' do
    it { should belong_to(:anti_habit) }
    it { should belong_to(:user) }
  end

  describe 'validations' do
    let(:user) { create(:user) }
    let(:other_user) { create(:user) }
    let(:anti_habit) { create(:anti_habit, user: user) }
    let(:other_anti_habit) { create(:anti_habit, user: user) }

    context '同一user・anti_habit・kindの場合' do
      it '無効である' do
        create(:reaction, user: other_user, anti_habit: anti_habit, reaction_kind: :watching)
        reaction = build(:reaction, user: other_user, anti_habit: anti_habit, reaction_kind: :watching)
        expect(reaction).to be_invalid
      end
    end

    context '異なるkindの場合' do
      it '有効である' do
        create(:reaction, user: other_user, anti_habit: anti_habit, reaction_kind: :watching)
        reaction = build(:reaction, user: other_user, anti_habit: anti_habit, reaction_kind: :fighting)
        expect(reaction).to be_valid
      end
    end

    context '異なるanti_habitの場合' do
      it '有効である' do
        create(:reaction, user: other_user, anti_habit: anti_habit, reaction_kind: :watching)
        reaction = build(:reaction, user: other_user, anti_habit: other_anti_habit, reaction_kind: :watching)
        expect(reaction).to be_valid
      end
    end

    context '異なるuserの場合' do
      it '有効である' do
        third_user = create(:user)
        create(:reaction, user: other_user, anti_habit: anti_habit, reaction_kind: :watching)
        reaction = build(:reaction, user: third_user, anti_habit: anti_habit, reaction_kind: :watching)
        expect(reaction).to be_valid
      end
    end
  end

  describe 'enum' do
    it { should define_enum_for(:reaction_kind).with_values(watching: 0, fighting: 1, zen: 2, fire: 3) }
  end
end
