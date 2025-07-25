class CreateAntiHabitRecords < ActiveRecord::Migration[7.2]
  def change
    create_table :anti_habit_records do |t|
      t.date :recorded_on
      t.references :anti_habit, null: false, foreign_key: true

      t.timestamps
    end
  end
end
