class UsersController < ApplicationController
  def show
    unless current_user
      redirect_to anti_habits_path
      return
    end

    @user = current_user
    @anti_habits = current_user.anti_habits.order(created_at: :desc)
  end
end
