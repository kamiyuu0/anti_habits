class AntiHabitsController < ApplicationController
  before_action :authenticate_user!, except: [ :index, :show ]

  def index
    @tag_name = params[:tag].presence
    @q = AntiHabit.ransack(params[:q])

    @anti_habits =
      if @tag_name
        @q.result
          .publicly_visible
          .includes(:user, :tags, :reactions, :comments)
          .tagged_with(@tag_name)
          .order(created_at: :desc)
          .page(params[:page])
      else
        @q.result
          .publicly_visible
          .includes(:user, :tags, :reactions, :comments)
          .order(created_at: :desc)
          .page(params[:page])
      end

    @all_tags = Tag.order(:name)
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
    @anti_habit = AntiHabit.includes(:tags).find(params[:id])

    unless @anti_habit.is_public || current_user&.own?(@anti_habit)
      redirect_to anti_habits_path, alert: "このページにアクセスする権限がありません。"
      return
    end

    @today_record = @anti_habit.today_record if current_user&.own?(@anti_habit)
    @comments = @anti_habit.comments.includes(:user).order(created_at: :desc)

    # font sizeの計算
    font_size = if @anti_habit.title.length > 15
                  "25"
    elsif @anti_habit.title.length > 10
                  "30"
    else
                  "50"
    end


    # タグの設定
    @url = "https://res.cloudinary.com/antihabits/image/upload/l_text:Sawarabi%20Gothic_#{font_size}_solid:#{@anti_habit.title},co_rgb:333,w_500,c_fit/v1757602327/anti_habits_dynamic_ogp_zyyjyk.png"
    set_meta_tags(og: { image: @url }, twitter: { image: @url })
  end

  def edit
    @anti_habit = current_user.anti_habits.includes(:tags).find(params[:id])
    @anti_habit.tag_names = @anti_habit.tag_names_as_string
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
    params.require(:anti_habit).permit(:title, :description, :tag_names, :is_public)
  end
end
