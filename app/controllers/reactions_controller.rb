class ReactionsController < ApplicationController
  before_action :authenticate_user!
  def create
    @anti_habit = AntiHabit.find(params[:anti_habit_id])
    current_user.reaction(@anti_habit, reaction_params[:reaction_kind])
    respond_to do |format|
      format.turbo_stream { render "create_watching" }
    end
  end

  def destroy
    @anti_habit = AntiHabit.find(params[:anti_habit_id])
    current_user.unreaction(@anti_habit, reaction_params[:reaction_kind])
    respond_to do |format|
      format.turbo_stream { render "destroy_watching" }
    end
  end

  private

  def reaction_params
    params.require(:reaction).permit(:reaction_kind)
  end
end
