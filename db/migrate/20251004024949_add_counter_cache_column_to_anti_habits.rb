class AddCounterCacheColumnToAntiHabits < ActiveRecord::Migration[7.2]
  def change
    add_column :anti_habits, :comments_count, :integer, default: 0, null: false

    AntiHabit.find_each do | anti_habit |
      AntiHabit.reset_counters(anti_habit.id, :comments)
    end
  end
end
