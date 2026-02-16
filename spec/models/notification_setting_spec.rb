require 'rails_helper'

RSpec.describe NotificationSetting, type: :model do
  describe 'associations' do
    it { should belong_to(:anti_habit) }
  end

  describe 'validations' do
    it { should validate_presence_of(:notification_time) }
    it { should allow_value(true).for(:notification_enabled) }
    it { should allow_value(false).for(:notification_enabled) }
    it { should_not allow_value(nil).for(:notification_enabled) }
    it { should allow_value(true).for(:notify_on_reaction) }
    it { should allow_value(false).for(:notify_on_reaction) }
    it { should_not allow_value(nil).for(:notify_on_reaction) }
    it { should allow_value(true).for(:notify_on_comment) }
    it { should allow_value(false).for(:notify_on_comment) }
    it { should_not allow_value(nil).for(:notify_on_comment) }
  end
end
