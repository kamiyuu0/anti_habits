require 'rails_helper'

RSpec.describe Tag, type: :model do
  describe 'associations' do
    it { should have_many(:anti_habit_tags).dependent(:destroy) }
    it { should have_many(:anti_habits).through(:anti_habit_tags) }
  end

  describe 'validations' do
    subject { create(:tag, name: 'TestTag') }

    it { should validate_presence_of(:name) }
    it { should validate_uniqueness_of(:name) }
    it { should validate_length_of(:name).is_at_most(15) }
  end

  describe '.find_or_create_by_names' do
    context '新規タグ名の場合' do
      it 'タグを新規作成する' do
        expect {
          Tag.find_or_create_by_names([ '新規タグ1', '新規タグ2' ])
        }.to change(Tag, :count).by(2)
      end
    end

    context '既存タグ名の場合' do
      it '既存タグを再利用する' do
        create(:tag, name: '既存タグ')
        expect {
          result = Tag.find_or_create_by_names([ '既存タグ' ])
          expect(result.first.name).to eq('既存タグ')
        }.not_to change(Tag, :count)
      end
    end

    context '空白を含むタグ名の場合' do
      it '空白をトリムする' do
        tags = Tag.find_or_create_by_names([ ' タグ名 ' ])
        expect(tags.first.name).to eq('タグ名')
      end
    end

    context '空文字を含む場合' do
      it '空文字を除外する' do
        tags = Tag.find_or_create_by_names([ '', '  ', '有効タグ' ])
        expect(tags.size).to eq(1)
        expect(tags.first.name).to eq('有効タグ')
      end
    end

    context '空配列の場合' do
      it '空配列を返す' do
        expect(Tag.find_or_create_by_names([])).to eq([])
      end
    end
  end
end
