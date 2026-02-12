require 'rails_helper'

RSpec.describe User, type: :model do
  describe 'associations' do
    it { should have_many(:anti_habits).dependent(:destroy) }
    it { should have_many(:reactions).dependent(:destroy) }
    it { should have_many(:reaction_anti_habits).through(:reactions).source(:anti_habit) }
    it { should have_many(:comments).dependent(:destroy) }
    it { should have_many(:bookmarks).dependent(:destroy) }
    it { should have_many(:bookmarked_anti_habits).through(:bookmarks).source(:anti_habit) }
  end

  describe 'validations' do
    subject { create(:user) }

    it { should validate_presence_of(:name) }
    it { should validate_length_of(:name).is_at_most(10) }
    it 'nameの一意性を検証する' do
      create(:user, name: '重複テスト')
      user = build(:user, name: '重複テスト')
      expect(user).to be_invalid
      expect(user.errors[:name]).to be_present
    end

    describe 'password' do
      context '新規作成時にパスワードが空の場合' do
        it '無効である' do
          user = build(:user, password: '', password_confirmation: '')
          expect(user).to be_invalid
        end
      end

      context '新規作成時にパスワードが5文字以下の場合' do
        it '無効である' do
          user = build(:user, password: '12345', password_confirmation: '12345')
          expect(user).to be_invalid
        end
      end

      context '新規作成時にパスワードが6文字の場合' do
        it '有効である' do
          user = build(:user, password: '123456', password_confirmation: '123456')
          expect(user).to be_valid
        end
      end

      context '更新時にパスワードが空の場合' do
        it '有効である' do
          user = create(:user)
          user.name = '新しい名前'
          expect(user).to be_valid
        end
      end
    end
  end

  describe 'callbacks' do
    describe 'replace_email_taken_error' do
      context 'メールアドレスが重複している場合' do
        it 'emailエラーをクリアしてbaseエラー「登録できませんでした。」に置換する' do
          create(:user, email: 'duplicate@example.com')
          user = build(:user, email: 'duplicate@example.com')
          user.valid?
          expect(user.errors[:base]).to include('登録できませんでした。')
          expect(user.errors[:email]).to be_empty
        end

        it '他のフィールドエラーもクリアされる' do
          create(:user, email: 'duplicate@example.com')
          user = build(:user, email: 'duplicate@example.com', name: '')
          user.valid?
          expect(user.errors[:name]).to be_empty
          expect(user.errors[:base]).to include('登録できませんでした。')
        end
      end

      context 'メールアドレスが重複していない場合' do
        it 'エラーメッセージは変更されない' do
          user = build(:user, name: '')
          user.valid?
          expect(user.errors[:name]).to be_present
          expect(user.errors[:base]).to be_empty
        end
      end
    end
  end

  describe '#own?' do
    let(:user) { create(:user) }

    context '自分のオブジェクトの場合' do
      it 'trueを返す' do
        anti_habit = create(:anti_habit, user: user)
        expect(user.own?(anti_habit)).to be true
      end
    end

    context '他人のオブジェクトの場合' do
      it 'falseを返す' do
        other_user = create(:user)
        anti_habit = create(:anti_habit, user: other_user)
        expect(user.own?(anti_habit)).to be false
      end
    end

    context 'nilの場合' do
      it 'falseを返す' do
        expect(user.own?(nil)).to be false
      end
    end
  end

  describe '#reaction' do
    let(:user) { create(:user) }
    let(:anti_habit) { create(:anti_habit) }

    context '未リアクションの場合' do
      it '新規作成してReactionオブジェクトを返す' do
        expect {
          result = user.reaction(anti_habit, :watching)
          expect(result).to be_a(Reaction)
          expect(result).to be_persisted
        }.to change(Reaction, :count).by(1)
      end
    end

    context '既にリアクション済みの場合' do
      let!(:existing_reaction) { user.reaction(anti_habit, :watching) }

      it '作成せず既存のReactionを返す' do
        expect {
          result = user.reaction(anti_habit, :watching)
          expect(result).to eq(existing_reaction)
        }.not_to change(Reaction, :count)
      end
    end

    context '同じanti_habitに異なる種別でリアクションする場合' do
      before { user.reaction(anti_habit, :watching) }

      it '新規作成する' do
        expect {
          user.reaction(anti_habit, :fighting)
        }.to change(Reaction, :count).by(1)
      end
    end
  end

  describe '#unreaction' do
    let(:user) { create(:user) }
    let(:anti_habit) { create(:anti_habit) }

    context 'リアクションが存在する場合' do
      before { user.reaction(anti_habit, :watching) }

      it '削除してdestroyedオブジェクトを返す' do
        expect {
          result = user.unreaction(anti_habit, :watching)
          expect(result).to be_a(Reaction)
          expect(result).to be_destroyed
        }.to change(Reaction, :count).by(-1)
      end
    end

    context 'リアクションが存在しない場合' do
      it 'nilを返しcountは変化しない' do
        expect {
          result = user.unreaction(anti_habit, :watching)
          expect(result).to be_nil
        }.not_to change(Reaction, :count)
      end
    end
  end

  describe '#reaction?' do
    let(:user) { create(:user) }
    let(:anti_habit) { create(:anti_habit) }

    context 'リアクションが存在する場合' do
      before { user.reaction(anti_habit, :watching) }

      it 'trueを返す' do
        expect(user.reaction?(anti_habit, :watching)).to be true
      end
    end

    context 'リアクションが存在しない場合' do
      it 'falseを返す' do
        expect(user.reaction?(anti_habit, :watching)).to be false
      end
    end

    context '異なる種別の場合' do
      before { user.reaction(anti_habit, :watching) }

      it 'falseを返す' do
        expect(user.reaction?(anti_habit, :fighting)).to be false
      end
    end
  end

  describe '#bookmark' do
    let(:user) { create(:user) }
    let(:other_user) { create(:user) }
    let(:anti_habit) { create(:anti_habit, user: other_user, is_public: true) }

    context '未ブックマークの場合' do
      it '新規作成してBookmarkオブジェクトを返す' do
        expect {
          result = user.bookmark(anti_habit)
          expect(result).to be_a(Bookmark)
          expect(result).to be_persisted
        }.to change(Bookmark, :count).by(1)
      end
    end

    context '既にブックマーク済みの場合' do
      let!(:existing_bookmark) { user.bookmark(anti_habit) }

      it '作成せず既存のBookmarkを返す' do
        expect {
          result = user.bookmark(anti_habit)
          expect(result).to eq(existing_bookmark)
        }.not_to change(Bookmark, :count)
      end
    end
  end

  describe '#unbookmark' do
    let(:user) { create(:user) }
    let(:other_user) { create(:user) }
    let(:anti_habit) { create(:anti_habit, user: other_user, is_public: true) }

    context 'ブックマークが存在する場合' do
      before { user.bookmark(anti_habit) }

      it '削除してdestroyedオブジェクトを返す' do
        expect {
          result = user.unbookmark(anti_habit)
          expect(result).to be_a(Bookmark)
          expect(result).to be_destroyed
        }.to change(Bookmark, :count).by(-1)
      end
    end

    context 'ブックマークが存在しない場合' do
      it 'nilを返しcountは変化しない' do
        expect {
          result = user.unbookmark(anti_habit)
          expect(result).to be_nil
        }.not_to change(Bookmark, :count)
      end
    end
  end

  describe '#bookmarked?' do
    let(:user) { create(:user) }
    let(:other_user) { create(:user) }
    let(:anti_habit) { create(:anti_habit, user: other_user, is_public: true) }

    context 'ブックマークが存在する場合' do
      before { user.bookmark(anti_habit) }

      it 'trueを返す' do
        expect(user.bookmarked?(anti_habit)).to be true
      end
    end

    context 'ブックマークが存在しない場合' do
      it 'falseを返す' do
        expect(user.bookmarked?(anti_habit)).to be false
      end
    end
  end

  describe '#can_bookmark?' do
    let(:user) { create(:user) }
    let(:other_user) { create(:user) }

    context '公開かつ他人のanti_habitの場合' do
      it 'trueを返す' do
        anti_habit = create(:anti_habit, user: other_user, is_public: true)
        expect(user.can_bookmark?(anti_habit)).to be true
      end
    end

    context '公開かつ自分のanti_habitの場合' do
      it 'falseを返す' do
        anti_habit = create(:anti_habit, user: user, is_public: true)
        expect(user.can_bookmark?(anti_habit)).to be false
      end
    end

    context '非公開かつ他人のanti_habitの場合' do
      it 'falseを返す' do
        anti_habit = create(:anti_habit, user: other_user, is_public: false)
        expect(user.can_bookmark?(anti_habit)).to be false
      end
    end

    context '非公開かつ自分のanti_habitの場合' do
      it 'falseを返す' do
        anti_habit = create(:anti_habit, user: user, is_public: false)
        expect(user.can_bookmark?(anti_habit)).to be false
      end
    end
  end
end
