class AddIsNotificationEnabledColumnToNotificationSettings < ActiveRecord::Migration[7.2]
  def change
    add_column :notification_settings, :notification_enabled, :boolean, default: false
  end
end
