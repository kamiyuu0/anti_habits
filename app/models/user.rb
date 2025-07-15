class User < ApplicationRecord
  # Include default devise modules. Others available are:
  # :confirmable, :lockable, :timeoutable, :trackable and :omniauthable
  devise :database_authenticatable, :registerable,
         :recoverable, :rememberable, :validatable

  has_many :anti_habits, dependent: :destroy
  validates :name, presence: true, length: { maximum: 10 }, uniqueness: true

  def own?(object)
    id == object&.user_id
  end
end
