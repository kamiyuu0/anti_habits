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
  has_many :bookmarks, dependent: :destroy
  has_many :bookmarked_anti_habits, through: :bookmarks, source: :anti_habit

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

  def reaction(anti_habit, reaction_kind)
    existing_reaction = reactions.find_by(anti_habit: anti_habit, reaction_kind: reaction_kind)
    return existing_reaction if existing_reaction

    reactions.create!(anti_habit: anti_habit, reaction_kind: reaction_kind)
  end

  def unreaction(anti_habit, reaction_kind)
    reactions.find_by(anti_habit: anti_habit, reaction_kind: reaction_kind)&.destroy
  end

  def reaction?(anti_habit, reaction_kind)
    reactions.exists?(anti_habit: anti_habit, reaction_kind: reaction_kind)
  end

  def bookmark(anti_habit)
    existing_bookmark = bookmarks.find_by(anti_habit: anti_habit)
    return existing_bookmark if existing_bookmark

    bookmarks.create!(anti_habit: anti_habit)
  end

  def unbookmark(anti_habit)
    bookmarks.find_by(anti_habit: anti_habit)&.destroy
  end

  def bookmarked?(anti_habit)
    bookmarks.exists?(anti_habit: anti_habit)
  end

  def can_bookmark?(anti_habit)
    anti_habit.is_public? && !own?(anti_habit)
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
