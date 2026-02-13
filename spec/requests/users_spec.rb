require 'rails_helper'

RSpec.describe "Users", type: :request do
  let(:user) { create(:user) }

  describe "GET /users/:id (show)" do
    context '未認証ユーザー' do
      it '一覧ページにリダイレクトされる' do
        get user_path(user)
        expect(response).to redirect_to(anti_habits_path)
      end
    end

    context '認証済みユーザー' do
      before { sign_in user }

      it 'マイページを閲覧できる' do
        get user_path(user)
        expect(response).to have_http_status(:ok)
      end
    end
  end
end
