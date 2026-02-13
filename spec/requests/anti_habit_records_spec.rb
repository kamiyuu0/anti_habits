require 'rails_helper'

RSpec.describe "AntiHabitRecords", type: :request do
  let(:user) { create(:user) }
  let(:other_user) { create(:user) }
  let(:anti_habit) { create(:anti_habit, user: user) }

  describe "POST /anti_habit_records (create)" do
    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        post anti_habit_records_path, params: { anti_habit_id: anti_habit.id }
        expect(response).to redirect_to(new_user_session_path)
      end

      it '記録が作成されない' do
        expect {
          post anti_habit_records_path, params: { anti_habit_id: anti_habit.id }
        }.not_to change(AntiHabitRecord, :count)
      end
    end

    context '認証済みユーザー（オーナー）' do
      before { sign_in user }

      it '記録を作成できる' do
        expect {
          post anti_habit_records_path, params: { anti_habit_id: anti_habit.id }
        }.to change(AntiHabitRecord, :count).by(1)
      end

      it '成功メッセージが表示される' do
        post anti_habit_records_path, params: { anti_habit_id: anti_habit.id }
        expect(flash[:notice]).to eq('記録を作成しました。')
      end

      it 'AntiHabit詳細ページにリダイレクトされる' do
        post anti_habit_records_path, params: { anti_habit_id: anti_habit.id }
        expect(response).to redirect_to(anti_habit_path(anti_habit))
      end
    end

    context '他人のAntiHabitの場合' do
      before { sign_in other_user }

      it '記録できない' do
        expect {
          post anti_habit_records_path, params: { anti_habit_id: anti_habit.id }
        }.not_to change(AntiHabitRecord, :count)
      end

      it 'alertメッセージでリダイレクトされる' do
        post anti_habit_records_path, params: { anti_habit_id: anti_habit.id }
        expect(response).to redirect_to(anti_habit_path(anti_habit))
        expect(flash[:alert]).to eq('自分の悪習慣のみ記録できます。')
      end
    end

    context '本日既に記録済みの場合' do
      before do
        sign_in user
        create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.today)
      end

      it '作成できない' do
        expect {
          post anti_habit_records_path, params: { anti_habit_id: anti_habit.id }
        }.not_to change(AntiHabitRecord, :count)
      end

      it 'alertメッセージでリダイレクトされる' do
        post anti_habit_records_path, params: { anti_habit_id: anti_habit.id }
        expect(response).to redirect_to(anti_habit_path(anti_habit))
        expect(flash[:alert]).to eq('今日はすでに記録済みです。')
      end
    end
  end

  describe "DELETE /anti_habit_records/:id (destroy)" do
    let!(:record) { create(:anti_habit_record, anti_habit: anti_habit, recorded_on: Time.zone.today) }

    context '未認証ユーザー' do
      it 'ログインページにリダイレクトされる' do
        delete anti_habit_record_path(record)
        expect(response).to redirect_to(new_user_session_path)
      end

      it '記録が削除されない' do
        expect {
          delete anti_habit_record_path(record)
        }.not_to change(AntiHabitRecord, :count)
      end
    end

    context 'オーナー' do
      before { sign_in user }

      it '記録を削除できる' do
        expect {
          delete anti_habit_record_path(record)
        }.to change(AntiHabitRecord, :count).by(-1)
      end

      it '303リダイレクトされる' do
        delete anti_habit_record_path(record)
        expect(response).to have_http_status(:see_other)
        expect(response).to redirect_to(anti_habit_path(anti_habit))
      end
    end

    context '他人の記録の場合' do
      before { sign_in other_user }

      it '削除できない' do
        expect {
          delete anti_habit_record_path(record)
        }.not_to change(AntiHabitRecord, :count)
      end

      it 'alertメッセージでリダイレクトされる' do
        delete anti_habit_record_path(record)
        expect(response).to redirect_to(anti_habit_path(anti_habit))
        expect(flash[:alert]).to eq('自分の記録のみ削除できます。')
      end
    end
  end
end
