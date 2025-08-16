class User < ApplicationRecord
  # Include default devise modules. Others available are:
  # :confirmable, :lockable, :timeoutable, :trackable and :omniauthable
  devise :database_authenticatable, :registerable,
         :recoverable, :rememberable, :validatable,
         :omniauthable, omniauth_providers: %i[line]

  has_many :anti_habits, dependent: :destroy
  has_many :reactions, dependent: :destroy
  has_many :reaction_anti_habits, through: :reactions, source: :anti_habit
  has_many :comments, dependent: :destroy

  validates :name, presence: true, length: { maximum: 10 }, uniqueness: true
  validates :password, presence: true, length: { minimum: 6 }, on: :create

  after_validation :replace_email_taken_error

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

  private

  def replace_email_taken_error
    if errors.details[:email]&.any? { |d| d[:error] == :taken }
      # 既存のすべてのエラーをクリア
      errors.clear
      # baseエラーとして追加（属性名が前に付かない）
      errors.add(:base, "登録できませんでした。")
    end
  end
end
