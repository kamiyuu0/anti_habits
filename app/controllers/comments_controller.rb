class CommentsController < ApplicationController
  before_action :authenticate_user!
  before_action :set_anti_habit

  def create
    @comment = @anti_habit.comments.build(comment_params)
    @comment.user = current_user
    @comment.save
  end

  private

  def set_anti_habit
    @anti_habit = AntiHabit.includes(:tags).find(params[:anti_habit_id])
  end

  def comment_params
    params.require(:comment).permit(:body)
  end
end
