
require 'rails_helper'

RSpec.describe "AntiHabits", type: :request do
  let(:user) { create(:user) }
  let(:other_user) { create(:user) }

  describe "POST /anti_habits (create)" do
    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        post anti_habits_path, params: { anti_habit: { title: 'テスト' } }
        expect(response).to redirect_to(new_user_session_path)
      end

      it 'AntiHabitが作成されない' do
        expect {
          post anti_habits_path, params: { anti_habit: { title: 'テスト' } }
        }.not_to change(AntiHabit, :count)
      end
    end

    context '認証済みユーザー' do
      before { sign_in user }

      context '有効なパラメータの場合' do
        let(:valid_params) do
          {
            anti_habit: {
              title: '禁煙',
              description: '健康のために禁煙します',
              is_public: true,
              goal_days: 30
            }
          }
        end

        it 'AntiHabitが作成される' do
          expect {
            post anti_habits_path, params: valid_params
          }.to change(AntiHabit, :count).by(1)
        end

        it '作成されたAntiHabitは現在のユーザーに紐づく' do
          post anti_habits_path, params: valid_params
          expect(AntiHabit.last.user).to eq(user)
        end

        it '詳細ページにリダイレクトされる' do
          post anti_habits_path, params: valid_params
          expect(response).to redirect_to(anti_habit_path(AntiHabit.last))
        end

        it '成功メッセージが表示される' do
          post anti_habits_path, params: valid_params
          expect(flash[:notice]).to eq('悪習慣を登録しました。')
        end

        it '正しい属性が保存される' do
          post anti_habits_path, params: valid_params
          anti_habit = AntiHabit.last
          expect(anti_habit.title).to eq('禁煙')
          expect(anti_habit.description).to eq('健康のために禁煙します')
          expect(anti_habit.is_public).to be true
          expect(anti_habit.goal_days).to eq(30)
        end
      end

      context 'タグを指定した場合' do
        let(:params_with_tags) do
          {
            anti_habit: {
              title: '禁煙',
              tag_names: 'タグ1,タグ2,タグ3'
            }
          }
        end

        it 'タグが関連付けられる' do
          post anti_habits_path, params: params_with_tags
          anti_habit = AntiHabit.last
          expect(anti_habit.tags.count).to eq(3)
          expect(anti_habit.tags.pluck(:name)).to contain_exactly('タグ1', 'タグ2', 'タグ3')
        end

        it '既存のタグは再利用される' do
          existing_tag = create(:tag, name: '既存タグ')
          initial_tag_count = Tag.count

          params = {
            anti_habit: {
              title: '禁煙',
              tag_names: '既存タグ,新規タグ'
            }
          }

          post anti_habits_path, params: params
          expect(Tag.count).to eq(initial_tag_count + 1)  # 新規タグのみ追加
          expect(AntiHabit.last.tags).to include(existing_tag)
        end

        it 'タグ名の前後の空白はトリムされる' do
          params = {
            anti_habit: {
              title: '禁煙',
              tag_names: ' タグ1 , タグ2 '
            }
          }

          post anti_habits_path, params: params
          expect(AntiHabit.last.tags.pluck(:name)).to contain_exactly('タグ1', 'タグ2')
        end
      end

      context '無効なパラメータの場合' do
        let(:invalid_params) do
          {
            anti_habit: {
              title: '',  # 空のタイトル（必須項目）
              description: 'テスト'
            }
          }
        end

        it 'AntiHabitが作成されない' do
          expect {
            post anti_habits_path, params: invalid_params
          }.not_to change(AntiHabit, :count)
        end

        it '422ステータスを返す' do
          post anti_habits_path, params: invalid_params
          expect(response).to have_http_status(:unprocessable_entity)
        end
      end

      context 'タイトルが20文字を超える場合' do
        let(:invalid_params) do
          {
            anti_habit: {
              title: 'あ' * 21
            }
          }
        end

        it '422ステータスを返す' do
          post anti_habits_path, params: invalid_params
          expect(response).to have_http_status(:unprocessable_entity)
        end
      end

      context 'タグ名が15文字を超える場合' do
        let(:invalid_params) do
          {
            anti_habit: {
              title: '禁煙',
              tag_names: 'あ' * 16
            }
          }
        end

        it '422ステータスを返す' do
          post anti_habits_path, params: invalid_params
          expect(response).to have_http_status(:unprocessable_entity)
        end
      end

      context 'goal_daysの範囲外の値の場合' do
        it '0日の場合はバリデーションエラー' do
          params = { anti_habit: { title: '禁煙', goal_days: 0 } }
          post anti_habits_path, params: params
          expect(response).to have_http_status(:unprocessable_entity)
        end

        it '366日の場合はバリデーションエラー' do
          params = { anti_habit: { title: '禁煙', goal_days: 366 } }
          post anti_habits_path, params: params
          expect(response).to have_http_status(:unprocessable_entity)
        end

        it 'nilの場合は有効' do
          params = { anti_habit: { title: '禁煙', goal_days: nil } }
          expect {
            post anti_habits_path, params: params
          }.to change(AntiHabit, :count).by(1)
        end
      end

      context 'is_publicの指定' do
        it 'trueの場合は公開される' do
          params = { anti_habit: { title: '禁煙', is_public: true } }
          post anti_habits_path, params: params
          expect(AntiHabit.last.is_public).to be true
        end

        it 'falseの場合は非公開になる' do
          params = { anti_habit: { title: '禁煙', is_public: false } }
          post anti_habits_path, params: params
          expect(AntiHabit.last.is_public).to be false
        end
      end
    end
  end
end
