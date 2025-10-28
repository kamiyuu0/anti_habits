class CreateNotificationSettings < ActiveRecord::Migration[7.2]
  def change
    create_table :notification_settings do |t|
      t.time :notification_time
      t.boolean :notify_on_reaction
      t.boolean :notify_on_comment
      t.references :anti_habit, null: false, foreign_key: true

      t.timestamps
    end
  end
end
