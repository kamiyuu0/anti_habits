require 'rails_helper'

RSpec.describe "NotificationSettings", type: :request do
  let(:user) { create(:user) }
  let(:other_user) { create(:user) }
  let(:anti_habit) { create(:anti_habit, user: user) }

  describe "GET /anti_habits/:anti_habit_id/notification_setting/new (new)" do
    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        get new_anti_habit_notification_setting_path(anti_habit)
        expect(response).to redirect_to(new_user_session_path)
      end
    end

    context '認証済みユーザー（オーナー）' do
      before { sign_in user }

      it '200ステータスを返す' do
        get new_anti_habit_notification_setting_path(anti_habit)
        expect(response).to have_http_status(:ok)
      end
    end

    context '他人のAntiHabitの場合' do
      before { sign_in other_user }

      it 'リダイレクトされる' do
        get new_anti_habit_notification_setting_path(anti_habit)
        expect(response).to redirect_to(anti_habits_path)
      end
    end
  end

  describe "POST /anti_habits/:anti_habit_id/notification_setting (create)" do
    let(:valid_params) do
      {
        notification_setting: {
          notification_time: '08:00',
          notification_enabled: true,
          notify_on_reaction: true,
          notify_on_comment: true
        }
      }
    end

    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        post anti_habit_notification_setting_path(anti_habit), params: valid_params
        expect(response).to redirect_to(new_user_session_path)
      end
    end

    context '認証済みユーザー（オーナー）' do
      before { sign_in user }

      it '有効なパラメータで通知設定を作成できる' do
        expect {
          post anti_habit_notification_setting_path(anti_habit), params: valid_params
        }.to change(NotificationSetting, :count).by(1)
      end

      it '作成後にリダイレクトされる' do
        post anti_habit_notification_setting_path(anti_habit), params: valid_params
        expect(response).to redirect_to(anti_habit_path(anti_habit))
      end

      it '成功メッセージが表示される' do
        post anti_habit_notification_setting_path(anti_habit), params: valid_params
        expect(flash[:notice]).to eq('通知設定を保存しました。')
      end
    end

    context '他人のAntiHabitの場合' do
      before { sign_in other_user }

      it '通知設定を作成できない' do
        expect {
          post anti_habit_notification_setting_path(anti_habit), params: valid_params
        }.not_to change(NotificationSetting, :count)
      end

      it 'リダイレクトされる' do
        post anti_habit_notification_setting_path(anti_habit), params: valid_params
        expect(response).to redirect_to(anti_habits_path)
      end
    end
  end

  describe "GET /anti_habits/:anti_habit_id/notification_setting/edit (edit)" do
    let!(:notification_setting) { create(:notification_setting, anti_habit: anti_habit) }

    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        get edit_anti_habit_notification_setting_path(anti_habit)
        expect(response).to redirect_to(new_user_session_path)
      end
    end

    context '認証済みユーザー（オーナー）' do
      before { sign_in user }

      it '200ステータスを返す' do
        get edit_anti_habit_notification_setting_path(anti_habit)
        expect(response).to have_http_status(:ok)
      end
    end

    context '他人のAntiHabitの場合' do
      before { sign_in other_user }

      it 'リダイレクトされる' do
        get edit_anti_habit_notification_setting_path(anti_habit)
        expect(response).to redirect_to(anti_habits_path)
      end
    end
  end

  describe "PATCH /anti_habits/:anti_habit_id/notification_setting (update)" do
    let!(:notification_setting) { create(:notification_setting, anti_habit: anti_habit) }

    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        patch anti_habit_notification_setting_path(anti_habit), params: { notification_setting: { notification_time: '09:00' } }
        expect(response).to redirect_to(new_user_session_path)
      end
    end

    context '認証済みユーザー（オーナー）' do
      before { sign_in user }

      it '有効なパラメータで通知設定を更新できる' do
        patch anti_habit_notification_setting_path(anti_habit), params: { notification_setting: { notify_on_reaction: true } }
        expect(notification_setting.reload.notify_on_reaction).to be true
      end

      it '更新後にリダイレクトされる' do
        patch anti_habit_notification_setting_path(anti_habit), params: { notification_setting: { notify_on_reaction: true } }
        expect(response).to redirect_to(anti_habit_path(anti_habit))
      end

      it '成功メッセージが表示される' do
        patch anti_habit_notification_setting_path(anti_habit), params: { notification_setting: { notify_on_reaction: true } }
        expect(flash[:notice]).to eq('通知設定を更新しました。')
      end
    end

    context '他人のAntiHabitの場合' do
      before { sign_in other_user }

      it '通知設定を更新できない' do
        patch anti_habit_notification_setting_path(anti_habit), params: { notification_setting: { notify_on_reaction: true } }
        expect(notification_setting.reload.notify_on_reaction).to be false
      end

      it 'リダイレクトされる' do
        patch anti_habit_notification_setting_path(anti_habit), params: { notification_setting: { notify_on_reaction: true } }
        expect(response).to redirect_to(anti_habits_path)
      end
    end
  end
end
