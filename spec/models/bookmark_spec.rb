require 'rails_helper'

RSpec.describe Bookmark, type: :model do
  describe 'associations' do
    it { should belong_to(:user) }
    it { should belong_to(:anti_habit) }
  end

  describe 'validations' do
    let(:user) { create(:user) }
    let(:other_user) { create(:user) }
    let(:anti_habit) { create(:anti_habit, user: other_user, is_public: true) }

    context '同一user・anti_habitの場合' do
      it '無効である' do
        create(:bookmark, user: user, anti_habit: anti_habit)
        bookmark = build(:bookmark, user: user, anti_habit: anti_habit)
        expect(bookmark).to be_invalid
      end
    end

    context '異なるanti_habitの場合' do
      it '有効である' do
        other_anti_habit = create(:anti_habit, user: other_user, is_public: true)
        create(:bookmark, user: user, anti_habit: anti_habit)
        bookmark = build(:bookmark, user: user, anti_habit: other_anti_habit)
        expect(bookmark).to be_valid
      end
    end

    context '異なるuserの場合' do
      it '有効である' do
        third_user = create(:user)
        create(:bookmark, user: user, anti_habit: anti_habit)
        bookmark = build(:bookmark, user: third_user, anti_habit: anti_habit)
        expect(bookmark).to be_valid
      end
    end
  end

  describe 'cannot_bookmark_own_anti_habit' do
    let(:user) { create(:user) }

    context '自分のanti_habitをブックマークする場合' do
      it 'エラーになる' do
        anti_habit = create(:anti_habit, user: user, is_public: true)
        bookmark = build(:bookmark, user: user, anti_habit: anti_habit)
        expect(bookmark).to be_invalid
        expect(bookmark.errors[:base]).to include('自分の悪習慣はブックマークできません')
      end
    end

    context '他人のanti_habitをブックマークする場合' do
      it '有効である' do
        other_user = create(:user)
        anti_habit = create(:anti_habit, user: other_user, is_public: true)
        bookmark = build(:bookmark, user: user, anti_habit: anti_habit)
        expect(bookmark).to be_valid
      end
    end
  end

  describe 'anti_habit_must_be_public' do
    let(:user) { create(:user) }
    let(:other_user) { create(:user) }

    context '非公開のanti_habitをブックマークする場合' do
      it 'エラーになる' do
        anti_habit = create(:anti_habit, user: other_user, is_public: false)
        bookmark = build(:bookmark, user: user, anti_habit: anti_habit)
        expect(bookmark).to be_invalid
        expect(bookmark.errors[:base]).to include('非公開の悪習慣はブックマークできません')
      end
    end

    context '公開のanti_habitをブックマークする場合' do
      it '有効である' do
        anti_habit = create(:anti_habit, user: other_user, is_public: true)
        bookmark = build(:bookmark, user: user, anti_habit: anti_habit)
        expect(bookmark).to be_valid
      end
    end
  end
end
