class AddIsPublicToAntiHabits < ActiveRecord::Migration[7.2]
  def change
    add_column :anti_habits, :is_public, :boolean, default: true, null: false
  end
end
