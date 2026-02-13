require 'rails_helper'

RSpec.describe "StaticPages", type: :request do
  describe "GET / (top)" do
    it '200ステータスを返す' do
      get root_path
      expect(response).to have_http_status(:ok)
    end
  end

  describe "GET /terms (terms)" do
    it '200ステータスを返す' do
      get terms_path
      expect(response).to have_http_status(:ok)
    end
  end

  describe "GET /privacy (privacy)" do
    it '200ステータスを返す' do
      get privacy_path
      expect(response).to have_http_status(:ok)
    end
  end
end
