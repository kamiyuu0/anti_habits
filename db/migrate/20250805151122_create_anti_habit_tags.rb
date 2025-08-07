class CreateAntiHabitTags < ActiveRecord::Migration[7.2]
  def change
    create_table :anti_habit_tags do |t|
      t.references :anti_habit, null: false, foreign_key: true
      t.references :tag, null: false, foreign_key: true

      t.timestamps
    end
    add_index :anti_habit_tags, [ :anti_habit_id, :tag_id ], unique: true
  end
end
