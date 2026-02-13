require 'rails_helper'

RSpec.describe NotificationSetting, type: :model do
  describe 'associations' do
    it { should belong_to(:anti_habit) }
  end
end
