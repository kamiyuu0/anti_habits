require 'rails_helper'

RSpec.describe "Reactions", type: :request do
  let(:user) { create(:user) }
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
  end
end
