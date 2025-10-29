class NotifyLineJob < ApplicationJob
  queue_as :default

  def perform(user_id)
    user = User.find(user_id)
    puts "========= NotifyLineJob ========="
    puts "user.name: #{user.name}"
    puts "user.uid: #{user.uid}"
    puts "========= NotifyLineJob ========="
    # line_notification(user.uid)
  end


  private

  # TODO:リファクタリング
  def line_notification(uid)
    require "line/bot"
    client = Line::Bot::V2::MessagingApi::ApiClient.new(
      channel_access_token: ENV.fetch("LINE_CHANNEL_TOKEN")
    )

    message = Line::Bot::V2::MessagingApi::TextMessage.new( # No need to pass `type: "text"`
      text: "今日の悪習慣記録をつけよう！\n\nhttps://anti-habits.com/"
    )

    # User.all.each do |user|
    # next unless user.uid.present? # Ensure the user has a LINE UID
    request = Line::Bot::V2::MessagingApi::PushMessageRequest.new(
      to: uid,
      messages: [
        message
      ]
    )

    response, status_code, response_headers = client.push_message_with_http_info(
      push_message_request: request
    )
  end
end
