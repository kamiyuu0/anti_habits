class AddGoalColumnsToAntiHabits < ActiveRecord::Migration[7.2]
  def change
    add_column :anti_habits, :goal_days, :integer
    add_column :anti_habits, :goal_achieved, :boolean, default: false, null: false
  end
end
