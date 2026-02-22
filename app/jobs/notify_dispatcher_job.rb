class NotifyDispatcherJob < ApplicationJob
  queue_as :default

  def perform
    now = Time.current.utc.change(sec: 0)
    target_time = Time.parse("2000-01-01 #{now.strftime('%H:%M')}:00") # pgのtime型のデフォルト年月日は2000-01-01
    today = Time.zone.today

    recorded_today = AntiHabitRecord.where(recorded_on: today).select(:anti_habit_id)

    target_anti_habits = AntiHabit.joins(:notification_setting)
                                  .where(notification_settings: {
                                    notification_enabled: true,
                                    notification_time: target_time
                                  })
                                  .where.not(id: recorded_today)

    target_anti_habits.each do |anti_habit|
      NotifyLineJob.perform_later(anti_habit.user_id, anti_habit.id)
    end
  end
end
