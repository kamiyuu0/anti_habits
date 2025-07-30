class ReactionsController < ApplicationController
  before_action :authenticate_user!
  def create
    @anti_habit = AntiHabit.find(params[:anti_habit_id])
    current_user.reaction(@anti_habit)
  end

  def destroy
    @anti_habit = current_user.reactions.find(params[:id]).anti_habit
    current_user.unreaction(@anti_habit)
  end
end
