class TagsController < ApplicationController
  def index
    tags = Tag.order(:name).pluck(:name)
    render json: tags
  end
end
