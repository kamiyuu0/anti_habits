require 'rails_helper'

RSpec.describe "Reactions", type: :request do
  let(:user) { create(:user) }
  let(:other_user) { create(:user) }
  let(:anti_habit) { create(:anti_habit, is_public: true) }

  describe "POST /anti_habits/:anti_habit_id/reactions (create)" do
    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        post anti_habit_reactions_path(anti_habit), params: { reaction: { reaction_kind: 'watching' } }, as: :turbo_stream
        expect(response).to redirect_to(new_user_session_path)
      end
    end

    context '認証済みユーザー' do
      before { sign_in user }

      %w[watching fighting zen fire].each do |kind|
        context "#{kind}リアクションの場合" do
          it 'リアクションを追加できる' do
            expect {
              post anti_habit_reactions_path(anti_habit), params: { reaction: { reaction_kind: kind } }, as: :turbo_stream
            }.to change(Reaction, :count).by(1)
          end

          it 'Turbo Streamレスポンスが返される' do
            post anti_habit_reactions_path(anti_habit), params: { reaction: { reaction_kind: kind } }, as: :turbo_stream
            expect(response.media_type).to eq(Mime[:turbo_stream].to_s)
          end
        end
      end
    end

    context '他人の非公開AntiHabitの場合' do
      let(:private_anti_habit) { create(:anti_habit, user: other_user, is_public: false) }

      before { sign_in user }

      it 'リアクションを追加できない（403 Forbidden）' do
        expect {
          post anti_habit_reactions_path(private_anti_habit), params: { reaction: { reaction_kind: 'watching' } }, as: :turbo_stream
        }.not_to change(Reaction, :count)
      end

      it '403ステータスを返す' do
        post anti_habit_reactions_path(private_anti_habit), params: { reaction: { reaction_kind: 'watching' } }, as: :turbo_stream
        expect(response).to have_http_status(:forbidden)
      end
    end
  end

  describe "DELETE /anti_habits/:anti_habit_id/reactions/:id (destroy)" do
    context '未認証ユーザー' do
      let!(:reaction) { create(:reaction, user: user, anti_habit: anti_habit, reaction_kind: :watching) }

      it 'ログインページにリダイレクトされる' do
        delete anti_habit_reaction_path(anti_habit, reaction), params: { reaction: { reaction_kind: 'watching' } }, as: :turbo_stream
        expect(response).to redirect_to(new_user_session_path)
      end
    end

    context '認証済みユーザー' do
      before { sign_in user }

      %w[watching fighting zen fire].each do |kind|
        context "#{kind}リアクションの場合" do
          let!(:reaction) { create(:reaction, user: user, anti_habit: anti_habit, reaction_kind: kind) }

          it 'リアクションを削除できる' do
            expect {
              delete anti_habit_reaction_path(anti_habit, reaction), params: { reaction: { reaction_kind: kind } }, as: :turbo_stream
            }.to change(Reaction, :count).by(-1)
          end

          it 'Turbo Streamレスポンスが返される' do
            delete anti_habit_reaction_path(anti_habit, reaction), params: { reaction: { reaction_kind: kind } }, as: :turbo_stream
            expect(response.media_type).to eq(Mime[:turbo_stream].to_s)
          end
        end
      end
    end

    context '他人の非公開AntiHabitの場合' do
      let(:private_anti_habit) { create(:anti_habit, user: other_user, is_public: false) }
      let!(:reaction) { create(:reaction, user: user, anti_habit: private_anti_habit, reaction_kind: :watching) }

      before { sign_in user }

      it 'リアクションを削除できない（403 Forbidden）' do
        expect {
          delete anti_habit_reaction_path(private_anti_habit, reaction), params: { reaction: { reaction_kind: 'watching' } }, as: :turbo_stream
        }.not_to change(Reaction, :count)
      end

      it '403ステータスを返す' do
        delete anti_habit_reaction_path(private_anti_habit, reaction), params: { reaction: { reaction_kind: 'watching' } }, as: :turbo_stream
        expect(response).to have_http_status(:forbidden)
      end
    end
  end
end
