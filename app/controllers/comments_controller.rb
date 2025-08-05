class CommentsController < ApplicationController
  before_action :authenticate_user!
  before_action :set_anti_habit

  def create
    comment = @anti_habit.comments.build(comment_params)
    comment.user = current_user
    if comment.save
      redirect_to anti_habit_path(@anti_habit), notice: "応援メッセージを投稿しました"
    else
      redirect_to anti_habit_path(@anti_habit), notice: "応援メッセージに失敗しました"
    end
  end

  private

  def set_anti_habit
    @anti_habit = AntiHabit.find(params[:anti_habit_id])
  end

  def comment_params
    params.require(:comment).permit(:body).merge(anti_habit_id: params[:anti_habit_id])
  end
end
