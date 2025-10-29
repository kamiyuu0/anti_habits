class NotifyDispatcherJob < ApplicationJob
  queue_as :default

  def perform
    now = Time.current.utc.change(sec: 0)
    target_time = Time.parse("2000-01-01 #{now.strftime('%H:%M')}:00") # pgのtime型のデフォルト年月日は2000-01-01

    #TODO:リファクタリング
    target_anti_habits = AntiHabit.joins(:notification_setting)
                                  .where(notification_settings: {
                                    notification_enabled: true,
                                    notification_time: target_time
                                  })

    # 今日記録していない悪習慣に絞る
    target_anti_habits = target_anti_habits.select do |anti_habit|
      anti_habit.today_record.nil?
    end

    target_users = User.where(id: target_anti_habits.pluck(:user_id).uniq, provider: "line")

    target_users.find_each do |user|
      NotifyLineJob.perform_later(user.id)
    end
  end
end
