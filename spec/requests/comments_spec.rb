require 'rails_helper'

RSpec.describe "Comments", type: :request do
  let(:user) { create(:user) }
  let(:anti_habit) { create(:anti_habit, is_public: true) }

  describe "POST /anti_habits/:anti_habit_id/comments (create)" do
    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        post anti_habit_comments_path(anti_habit), params: { comment: { body: 'テストコメント' } }
        expect(response).to redirect_to(new_user_session_path)
      end

      it 'コメントが作成されない' do
        expect {
          post anti_habit_comments_path(anti_habit), params: { comment: { body: 'テストコメント' } }
        }.not_to change(Comment, :count)
      end
    end

    context '認証済みユーザー' do
      before { sign_in user }

      context '有効なパラメータの場合' do
        it 'コメントが作成される' do
          expect {
            post anti_habit_comments_path(anti_habit), params: { comment: { body: 'テストコメント' } }
          }.to change(Comment, :count).by(1)
        end

        it 'コメントが現在のユーザーに紐づく' do
          post anti_habit_comments_path(anti_habit), params: { comment: { body: 'テストコメント' } }
          expect(Comment.last.user).to eq(user)
        end

        it 'コメントが対象のAntiHabitに紐づく' do
          post anti_habit_comments_path(anti_habit), params: { comment: { body: 'テストコメント' } }
          expect(Comment.last.anti_habit).to eq(anti_habit)
        end
      end

      context '無効なパラメータの場合' do
        it '空bodyの場合はコメントが作成されない' do
          expect {
            post anti_habit_comments_path(anti_habit), params: { comment: { body: '' } }
          }.not_to change(Comment, :count)
        end
      end

      context '存在しないAntiHabitの場合' do
        it 'リダイレクトされる' do
          post anti_habit_comments_path(anti_habit_id: 0), params: { comment: { body: 'テストコメント' } }
          expect(response).to redirect_to(anti_habits_path)
        end
      end
    end
  end
end
