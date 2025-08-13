class ApplicationController < ActionController::Base
  # Only allow modern browsers supporting webp images, web push, badges, import maps, CSS nesting, and CSS :has.
  allow_browser versions: :modern

  rescue_from ActiveRecord::RecordNotFound, with: :record_not_found

  private

  def record_not_found
    if user_signed_in?
      redirect_to anti_habits_path, alert: "指定されたページが見つかりません。"
    else
      redirect_to root_path, alert: "指定されたページが見つかりません。"
    end
  end
end
