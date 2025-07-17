# frozen_string_literal: true

class Users::OmniauthCallbacksController < Devise::OmniauthCallbacksController
  # You should configure your model like this:
  # devise :omniauthable, omniauth_providers: [:twitter]
  def line
    if user_signed_in?
      link_line_account
    else
      basic_action
    end
  end

  private
  def basic_action
    @omniauth = request.env["omniauth.auth"]
    if @omniauth.present?
      @profile = User.find_or_initialize_by(provider: @omniauth["provider"], uid: @omniauth["uid"])
      if @profile.email.blank?
        email = @omniauth["info"]["email"] ? @omniauth["info"]["email"] : "#{@omniauth["uid"]}-#{@omniauth["provider"]}@example.com"
        @profile = current_user || User.create!(provider: @omniauth["provider"], uid: @omniauth["uid"], email: email, name: @omniauth["info"]["name"], password: Devise.friendly_token[0, 20])
      end
      @profile.set_values(@omniauth)
      sign_in(:user, @profile)
    end
    flash[:notice] = "ログインしました"
    redirect_to anti_habits_path
  end

  def link_line_account
    @omniauth = request.env["omniauth.auth"]

    unless @omniauth && @omniauth["provider"].present? && @omniauth["uid"].present?
      flash[:alert] = "LINE連携に失敗しました"
      return redirect_to user_path(current_user)
    end

    if User.exists?(provider: @omniauth["provider"], uid: @omniauth["uid"])
      flash[:alert] = "このLINEアカウントは既に登録されています"
    else
      current_user.update!(provider: @omniauth["provider"], uid: @omniauth["uid"])
      flash[:notice] = "LINEアカウントを紐付けました"
    end

    redirect_to user_path(current_user)
  end
  # You should also create an action method in this controller like this:
  # def twitter
  # end

  # More info at:
  # https://github.com/heartcombo/devise#omniauth

  # GET|POST /resource/auth/twitter
  # def passthru
  #   super
  # end

  # GET|POST /users/auth/twitter/callback
  # def failure
  #   super
  # end

  # protected

  # The path used when OmniAuth fails
  # def after_omniauth_failure_path_for(scope)
  #   super(scope)
  # end
end
