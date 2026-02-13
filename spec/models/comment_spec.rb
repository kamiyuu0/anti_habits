require 'rails_helper'

RSpec.describe Comment, type: :model do
  describe 'associations' do
    it { should belong_to(:anti_habit) }
    it { should belong_to(:user) }
  end

  describe 'validations' do
    it { should validate_presence_of(:body) }
    it { should validate_length_of(:body).is_at_most(500) }
  end

  describe 'counter_cache' do
    let(:user) { create(:user) }
    let(:anti_habit) { create(:anti_habit, user: user) }

    it 'コメント作成時にcomments_countが増加する' do
      expect {
        create(:comment, anti_habit: anti_habit, user: user)
      }.to change { anti_habit.reload.comments_count }.by(1)
    end

    it 'コメント削除時にcomments_countが減少する' do
      comment = create(:comment, anti_habit: anti_habit, user: user)
      expect {
        comment.destroy
      }.to change { anti_habit.reload.comments_count }.by(-1)
    end
  end
end
