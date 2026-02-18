class NotificationSettingsController < ApplicationController
  before_action :authenticate_user!
  before_action :set_anti_habit

  def new
    @notification_setting = @anti_habit.build_notification_setting
  end

  def create
    @notification_setting = @anti_habit.build_notification_setting(notification_setting_params)
    if @notification_setting.save
      redirect_to anti_habit_path(@anti_habit), notice: "通知設定を保存しました。"
    else
      render :new, status: :unprocessable_entity
    end
  end

  def edit
    @notification_setting = @anti_habit.notification_setting
  end

  def update
    @notification_setting = @anti_habit.notification_setting
    if @notification_setting.update(notification_setting_params)
      redirect_to anti_habit_path(@anti_habit), notice: "通知設定を更新しました。"
    else
      render :edit, status: :unprocessable_entity
    end
  end

  private

  def set_anti_habit
    @anti_habit = current_user.anti_habits.find(params[:anti_habit_id])
  end

  def notification_setting_params
    params.require(:notification_setting).permit(:notification_time, :notification_enabled, :notify_on_reaction, :notify_on_comment)
  end
end
