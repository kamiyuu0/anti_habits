require 'rails_helper'

RSpec.describe "Bookmarks", type: :request do
  let(:user) { create(:user) }
  let(:other_user) { create(:user) }

  describe "GET /bookmarks (index)" do
    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        get bookmarks_path
        expect(response).to redirect_to(new_user_session_path)
      end
    end

    context '認証済みユーザー' do
      before { sign_in user }

      it 'ブックマーク一覧を取得できる' do
        get bookmarks_path
        expect(response).to have_http_status(:ok)
      end
    end
  end

  describe "POST /anti_habits/:anti_habit_id/bookmarks (create)" do
    context '未認証ユーザー' do
      let(:anti_habit) { create(:anti_habit, is_public: true) }

      it 'ログインページにリダイレクトされる' do
        post anti_habit_bookmarks_path(anti_habit), as: :turbo_stream
        expect(response).to redirect_to(new_user_session_path)
      end
    end

    context '認証済みユーザー' do
      before { sign_in user }

      context '他人の公開AntiHabitの場合' do
        let(:anti_habit) { create(:anti_habit, user: other_user, is_public: true) }

        it 'ブックマークできる' do
          expect {
            post anti_habit_bookmarks_path(anti_habit), as: :turbo_stream
          }.to change(Bookmark, :count).by(1)
        end

        it 'Turbo Streamレスポンスが返される' do
          post anti_habit_bookmarks_path(anti_habit), as: :turbo_stream
          expect(response.media_type).to eq(Mime[:turbo_stream].to_s)
        end
      end

      context '自分のAntiHabitの場合' do
        let(:anti_habit) { create(:anti_habit, user: user, is_public: true) }

        it 'ブックマークできない（403 Forbidden）' do
          post anti_habit_bookmarks_path(anti_habit), as: :turbo_stream
          expect(response).to have_http_status(:forbidden)
        end
      end

      context '非公開のAntiHabitの場合' do
        let(:anti_habit) { create(:anti_habit, user: other_user, is_public: false) }

        it 'ブックマークできない（403 Forbidden）' do
          post anti_habit_bookmarks_path(anti_habit), as: :turbo_stream
          expect(response).to have_http_status(:forbidden)
        end
      end
    end
  end

  describe "DELETE /anti_habits/:anti_habit_id/bookmarks/:id (destroy)" do
    let(:anti_habit) { create(:anti_habit, user: other_user, is_public: true) }
    let!(:bookmark) { create(:bookmark, user: user, anti_habit: anti_habit) }

    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        delete anti_habit_bookmark_path(anti_habit, bookmark), as: :turbo_stream
        expect(response).to redirect_to(new_user_session_path)
      end
    end

    context '認証済みユーザー' do
      before { sign_in user }

      it 'ブックマークを解除できる' do
        expect {
          delete anti_habit_bookmark_path(anti_habit, bookmark), as: :turbo_stream
        }.to change(Bookmark, :count).by(-1)
      end

      it 'Turbo Streamレスポンスが返される' do
        delete anti_habit_bookmark_path(anti_habit, bookmark), as: :turbo_stream
        expect(response.media_type).to eq(Mime[:turbo_stream].to_s)
      end
    end
  end
end
