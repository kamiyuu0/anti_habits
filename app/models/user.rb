class User < ApplicationRecord
  # Include default devise modules. Others available are:
  # :confirmable, :lockable, :timeoutable, :trackable and :omniauthable
  devise :database_authenticatable, :registerable,
         :recoverable, :rememberable, :validatable,
         :omniauthable, omniauth_providers: %i[line]

  has_many :anti_habits, dependent: :destroy
  has_many :reactions, dependent: :destroy
  has_many :reaction_anti_habits, through: :reactions, source: :anti_habit

  validates :name, presence: true, length: { maximum: 10 }, uniqueness: true
  validates :password, presence: true, length: { minimum: 6 }, on: :create

  def set_values(omniauth)
    return if provider.to_s != omniauth["provider"].to_s || uid != omniauth["uid"]
    credentials = omniauth["credentials"]
    info = omniauth["info"]

    access_token = credentials["refresh_token"]
    access_secret = credentials["secret"]
    credentials = credentials.to_json
    name = info["name"]
  end

  def own?(object)
    id == object&.user_id
  end

  def reaction(anti_habit)
    reaction_anti_habits << anti_habit
  end

  def unreaction(anti_habit)
    reaction_anti_habits.destroy(anti_habit)
  end

  def reaction?(anti_habit)
    reaction_anti_habits.include?(anti_habit)
  end
end
