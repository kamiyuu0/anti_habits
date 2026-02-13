
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
          expect(response).to have_http_status(:unprocessable_content)
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
          expect(response).to have_http_status(:unprocessable_content)
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
          expect(response).to have_http_status(:unprocessable_content)
        end
      end

      context 'goal_daysの範囲外の値の場合' do
        it '0日の場合はバリデーションエラー' do
          params = { anti_habit: { title: '禁煙', goal_days: 0 } }
          post anti_habits_path, params: params
          expect(response).to have_http_status(:unprocessable_content)
        end

        it '366日の場合はバリデーションエラー' do
          params = { anti_habit: { title: '禁煙', goal_days: 366 } }
          post anti_habits_path, params: params
          expect(response).to have_http_status(:unprocessable_content)
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

  describe "GET /anti_habits (index)" do
    let!(:public_anti_habit) { create(:anti_habit, is_public: true) }
    let!(:private_anti_habit) { create(:anti_habit, is_public: false) }

    it '200ステータスを返す' do
      get anti_habits_path
      expect(response).to have_http_status(:ok)
    end

    it '公開されたAntiHabitが表示される' do
      get anti_habits_path
      expect(response.body).to include(public_anti_habit.title)
    end

    it '非公開のAntiHabitは表示されない' do
      get anti_habits_path
      expect(response.body).not_to include(private_anti_habit.title)
    end
  end

  describe "GET /anti_habits/:id (show)" do
    context '公開AntiHabitの場合' do
      let(:public_anti_habit) { create(:anti_habit, is_public: true) }

      it '誰でも閲覧できる' do
        get anti_habit_path(public_anti_habit)
        expect(response).to have_http_status(:ok)
      end
    end

    context '非公開AntiHabitの場合' do
      let(:private_anti_habit) { create(:anti_habit, user: user, is_public: false) }

      it 'オーナーは閲覧できる' do
        sign_in user
        get anti_habit_path(private_anti_habit)
        expect(response).to have_http_status(:ok)
      end

      it '他人がアクセスするとリダイレクトされる' do
        sign_in other_user
        get anti_habit_path(private_anti_habit)
        expect(response).to redirect_to(anti_habits_path)
      end

      it '未認証ユーザーがアクセスするとリダイレクトされる' do
        get anti_habit_path(private_anti_habit)
        expect(response).to redirect_to(anti_habits_path)
      end
    end

    context '存在しないIDの場合' do
      it 'リダイレクトされる' do
        get anti_habit_path(id: 0)
        expect(response).to redirect_to(root_path)
      end

      it '認証済みユーザーはanti_habits_pathにリダイレクトされる' do
        sign_in user
        get anti_habit_path(id: 0)
        expect(response).to redirect_to(anti_habits_path)
      end
    end
  end

  describe "GET /anti_habits/new (new)" do
    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        get new_anti_habit_path
        expect(response).to redirect_to(new_user_session_path)
      end
    end

    context '認証済みユーザー' do
      before { sign_in user }

      it '200ステータスを返す' do
        get new_anti_habit_path
        expect(response).to have_http_status(:ok)
      end
    end
  end

  describe "GET /anti_habits/:id/edit (edit)" do
    let(:anti_habit) { create(:anti_habit, user: user) }

    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        get edit_anti_habit_path(anti_habit)
        expect(response).to redirect_to(new_user_session_path)
      end
    end

    context 'オーナー' do
      before { sign_in user }

      it '200ステータスを返す' do
        get edit_anti_habit_path(anti_habit)
        expect(response).to have_http_status(:ok)
      end
    end

    context '他人のAntiHabit' do
      before { sign_in other_user }

      it 'アクセスできない（リダイレクトされる）' do
        get edit_anti_habit_path(anti_habit)
        expect(response).to redirect_to(anti_habits_path)
      end
    end
  end

  describe "PATCH /anti_habits/:id (update)" do
    let(:anti_habit) { create(:anti_habit, user: user, title: '元のタイトル') }

    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        patch anti_habit_path(anti_habit), params: { anti_habit: { title: '新しいタイトル' } }
        expect(response).to redirect_to(new_user_session_path)
      end
    end

    context 'オーナー' do
      before { sign_in user }

      it '有効なパラメータで更新できる' do
        patch anti_habit_path(anti_habit), params: { anti_habit: { title: '新しいタイトル' } }
        expect(anti_habit.reload.title).to eq('新しいタイトル')
      end

      it '更新後にリダイレクトされる' do
        patch anti_habit_path(anti_habit), params: { anti_habit: { title: '新しいタイトル' } }
        expect(response).to redirect_to(anti_habit_path(anti_habit))
      end

      it '成功メッセージが表示される' do
        patch anti_habit_path(anti_habit), params: { anti_habit: { title: '新しいタイトル' } }
        expect(flash[:notice]).to eq('悪習慣を更新しました。')
      end

      it '無効なパラメータの場合は422ステータスを返す' do
        patch anti_habit_path(anti_habit), params: { anti_habit: { title: '' } }
        expect(response).to have_http_status(:unprocessable_content)
      end
    end

    context '他人のAntiHabit' do
      before { sign_in other_user }

      it '更新できない（リダイレクトされる）' do
        patch anti_habit_path(anti_habit), params: { anti_habit: { title: '新しいタイトル' } }
        expect(response).to redirect_to(anti_habits_path)
        expect(anti_habit.reload.title).to eq('元のタイトル')
      end
    end
  end

  describe "DELETE /anti_habits/:id (destroy)" do
    let!(:anti_habit) { create(:anti_habit, user: user) }

    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        delete anti_habit_path(anti_habit)
        expect(response).to redirect_to(new_user_session_path)
      end

      it 'AntiHabitが削除されない' do
        expect {
          delete anti_habit_path(anti_habit)
        }.not_to change(AntiHabit, :count)
      end
    end

    context 'オーナー' do
      before { sign_in user }

      it '削除できる' do
        expect {
          delete anti_habit_path(anti_habit)
        }.to change(AntiHabit, :count).by(-1)
      end

      it '303リダイレクトされる' do
        delete anti_habit_path(anti_habit)
        expect(response).to have_http_status(:see_other)
        expect(response).to redirect_to(anti_habits_path)
      end
    end

    context '他人のAntiHabit' do
      before { sign_in other_user }

      it '削除できない（リダイレクトされる）' do
        expect {
          delete anti_habit_path(anti_habit)
        }.not_to change(AntiHabit, :count)
        expect(response).to redirect_to(anti_habits_path)
      end
    end
  end

  describe "GET /anti_habits/autocomplete (autocomplete)" do
    let!(:public_anti_habit) { create(:anti_habit, title: '禁煙チャレンジ', is_public: true) }

    it 'クエリに一致する公開AntiHabitが返される' do
      get autocomplete_anti_habits_path, params: { q: '禁煙' }
      expect(response).to have_http_status(:ok)
      expect(response.body).to include('禁煙チャレンジ')
    end

    it '空クエリの場合は結果なし' do
      get autocomplete_anti_habits_path, params: { q: '' }
      expect(response).to have_http_status(:ok)
      expect(response.body).not_to include('禁煙チャレンジ')
    end
  end
end
