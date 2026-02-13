require 'rails_helper'

RSpec.describe "Tags", type: :request do
  describe "GET /tags (index)" do
    let!(:tag_b) { create(:tag, name: 'Bタグ') }
    let!(:tag_a) { create(:tag, name: 'Aタグ') }
    let!(:tag_c) { create(:tag, name: 'Cタグ') }

    it 'タグ一覧がJSON形式で返される' do
      get tags_path
      expect(response).to have_http_status(:ok)
      expect(response.content_type).to include('application/json')
    end

    it '名前順にソートされている' do
      get tags_path
      tags = JSON.parse(response.body)
      expect(tags).to eq([ 'Aタグ', 'Bタグ', 'Cタグ' ])
    end
  end
end
