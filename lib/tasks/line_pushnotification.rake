namespace :line_pushnotification do
  desc "LINE通知を送ります"
  task send: :environment do
    client = Line::Bot::V2::MessagingApi::ApiClient.new(
      channel_access_token: ENV.fetch("LINE_CHANNEL_TOKEN")
    )

    message = Line::Bot::V2::MessagingApi::TextMessage.new( # No need to pass `type: "text"`
      text: "今日の悪習慣進捗を登録しましょう \n https://anti-habits.onrender.com/"
    )

    User.all.each do |user|
      next unless user.uid.present? # Ensure the user has a LINE UID
      request = Line::Bot::V2::MessagingApi::PushMessageRequest.new(
        to: user.uid,
        messages: [
          message
        ]
      )

      response, status_code, response_headers = client.push_message_with_http_info(
        push_message_request: request
      )

      puts response.class
    end
  end
end
