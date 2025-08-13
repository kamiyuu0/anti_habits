class CommentsController < ApplicationController
  before_action :authenticate_user!
  before_action :set_anti_habit

  def create
    @comment = @anti_habit.comments.build(comment_params)
    @comment.user = current_user
    if @comment.save
      redirect_to anti_habit_path(@anti_habit), notice: "応援メッセージを投稿しました"
    else
      # エラー時はshowページを再表示（エラーメッセージ付き）
      @today_record = @anti_habit.today_record if current_user&.own?(@anti_habit)
      @comments = @anti_habit.comments.includes(:user).order(created_at: :desc)
      render "anti_habits/show", status: :unprocessable_entity
    end
  end

  private

  def set_anti_habit
    @anti_habit = AntiHabit.includes(:tags).find(params[:anti_habit_id])
  end

  def comment_params
    params.require(:comment).permit(:body)
  end
end
