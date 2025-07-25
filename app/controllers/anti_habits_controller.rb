class AntiHabitsController < ApplicationController
  before_action :authenticate_user!, except: [ :index, :show ]

  def index
    @anti_habits = AntiHabit.all.includes(:user).order(created_at: :desc)
  end

  def new
    @anti_habit = AntiHabit.new
  end

  def create
    @anti_habit = current_user.anti_habits.build(anti_habit_params)
    if @anti_habit.save
      redirect_to anti_habit_path(@anti_habit), notice: "悪習慣を登録しました。"
    else
      render :new, status: :unprocessable_entity
    end
  end

  def show
    @anti_habit = AntiHabit.find(params[:id])
    @today_record = @anti_habit.today_record if current_user.own?(@anti_habit)
  end

  def edit
    @anti_habit = current_user.anti_habits.find(params[:id])
  end

  def update
    @anti_habit = current_user.anti_habits.find(params[:id])
    if @anti_habit.update(anti_habit_params)
      redirect_to anti_habit_path(@anti_habit), notice: "悪習慣を更新しました。"
    else
      render :edit, status: :unprocessable_entity
    end
  end

  def destroy
    @anti_habit = current_user.anti_habits.find(params[:id])
    @anti_habit.destroy!
    redirect_to anti_habits_path, notice: "悪習慣を削除しました。", status: :see_other
  end

  private

  def anti_habit_params
    params.require(:anti_habit).permit(:title, :description)
  end
end
