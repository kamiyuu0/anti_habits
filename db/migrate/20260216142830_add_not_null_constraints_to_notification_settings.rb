class AddNotNullConstraintsToNotificationSettings < ActiveRecord::Migration[7.2]
  def change
    change_column_null :notification_settings, :notification_time, false
    change_column_null :notification_settings, :notification_enabled, false, false
    change_column_null :notification_settings, :notify_on_reaction, false, false
    change_column_null :notification_settings, :notify_on_comment, false, false
    change_column_default :notification_settings, :notify_on_reaction, false
    change_column_default :notification_settings, :notify_on_comment, false
  end
end
