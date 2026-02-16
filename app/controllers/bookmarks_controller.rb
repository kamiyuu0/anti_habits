class BookmarksController < ApplicationController
  before_action :authenticate_user!

  def index
    @bookmarked_anti_habits = current_user
      .bookmarked_anti_habits
      .publicly_visible
      .with_associations
      .order("bookmarks.created_at DESC")
      .page(params[:page])
  end

  def create
    @anti_habit = AntiHabit.find(params[:anti_habit_id])

    unless current_user.can_bookmark?(@anti_habit)
      head :forbidden
      return
    end

    current_user.bookmark(@anti_habit)

    respond_to do |format|
      format.turbo_stream { render "create" }
    end
  end

  def destroy
    @anti_habit = AntiHabit.find(params[:anti_habit_id])
    current_user.unbookmark(@anti_habit)

    respond_to do |format|
      format.turbo_stream { render "destroy" }
    end
  end
end
