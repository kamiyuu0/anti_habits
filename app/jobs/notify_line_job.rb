require "line/bot"

class NotifyLineJob < ApplicationJob
  queue_as :default

  def perform(user_id, anti_habit_id)
    user = User.find(user_id)
    anti_habit = AntiHabit.find(anti_habit_id)
    line_notification(user.uid, anti_habit.title)
  end

  private

  def line_notification(uid, anti_habit_title)
    client = Line::Bot::V2::MessagingApi::ApiClient.new(
      channel_access_token: ENV.fetch("LINE_CHANNEL_TOKEN")
    )
    message = Line::Bot::V2::MessagingApi::TextMessage.new(
      text: "今日の「#{anti_habit_title}」の記録をつけよう！\n\nhttps://anti-habits.com/"
    )
    request = Line::Bot::V2::MessagingApi::PushMessageRequest.new(
      to: uid,
      messages: [ message ]
    )
    client.push_message_with_http_info(push_message_request: request)
  end
end
