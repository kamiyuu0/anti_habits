class NotificationSettingsController < ApplicationController
  before_action :authenticate_user!

  def new
    @anti_habit = AntiHabit.find(params[:anti_habit_id])
    @notification_setting = @anti_habit.build_notification_setting
  end

  def create
    @anti_habit = AntiHabit.find(params[:anti_habit_id])
    @notification_setting = @anti_habit.build_notification_setting(notification_setting_params)
    if @notification_setting.save
      redirect_to anti_habit_path(@anti_habit), notice: "通知設定を保存しました。"
    else
      render :new, status: :unprocessable_entity
    end
  end

  def edit
    @anti_habit = AntiHabit.find(params[:anti_habit_id])
    @notification_setting = @anti_habit.notification_setting
  end

  def update
    @anti_habit = AntiHabit.find(params[:anti_habit_id])
    @notification_setting = @anti_habit.notification_setting
    if @notification_setting.update(notification_setting_params)
      redirect_to anti_habit_path(@anti_habit), notice: "通知設定を更新しました。"
    else
      render :edit, status: :unprocessable_entity
    end
  end

  private

  def notification_setting_params
    params.require(:notification_setting).permit(:notification_time, :notification_enabled, :notify_on_reaction, :notify_on_comment)
  end
end
