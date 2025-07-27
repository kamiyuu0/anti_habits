class AntiHabitRecordsController < ApplicationController
  before_action :authenticate_user!

  def create
    @anti_habit = AntiHabit.find(params[:anti_habit_id])
    if !current_user.own?(@anti_habit)
      redirect_to @anti_habit, alert: "自分の悪習慣のみ記録できます。"
      return
    end

    if @anti_habit.today_record
      redirect_to @anti_habit, alert: "今日はすでに記録済みです。"
      return
    end

    @record = @anti_habit.anti_habit_records.build
    @record.recorded_on = Time.zone.today
    if @record.save
      redirect_to @anti_habit, notice: "記録を作成しました。"
    else
      redirect_to @anti_habit, alert: "記録の作成に失敗しました。"
    end
  end

  def destroy
    @record = AntiHabitRecord.find(params[:id])
    @anti_habit = @record.anti_habit
    if !current_user.own?(@anti_habit)
      redirect_to @anti_habit, alert: "自分の記録のみ削除できます。"
      return
    end

    @record.destroy
    redirect_to @anti_habit, notice: "記録を削除しました。", status: :see_other
  end
end
