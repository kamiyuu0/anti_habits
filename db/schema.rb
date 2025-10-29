# This file is auto-generated from the current state of the database. Instead
# of editing this file, please use the migrations feature of Active Record to
# incrementally modify your database, and then regenerate this schema definition.
#
# This file is the source Rails uses to define your schema when running `bin/rails
# db:schema:load`. When creating a new database, `bin/rails db:schema:load` tends to
# be faster and is potentially less error prone than running all of your
# migrations from scratch. Old migrations may fail to apply correctly if those
# migrations use external dependencies or application code.
#
# It's strongly recommended that you check this file into your version control system.

ActiveRecord::Schema[7.2].define(version: 2025_10_29_073431) do
  # These are extensions that must be enabled in order to support this database
  enable_extension "plpgsql"

  create_table "anti_habit_records", force: :cascade do |t|
    t.date "recorded_on"
    t.bigint "anti_habit_id", null: false
    t.datetime "created_at", null: false
    t.datetime "updated_at", null: false
    t.index ["anti_habit_id"], name: "index_anti_habit_records_on_anti_habit_id"
  end

  create_table "anti_habit_tags", force: :cascade do |t|
    t.bigint "anti_habit_id", null: false
    t.bigint "tag_id", null: false
    t.datetime "created_at", null: false
    t.datetime "updated_at", null: false
    t.index ["anti_habit_id", "tag_id"], name: "index_anti_habit_tags_on_anti_habit_id_and_tag_id", unique: true
    t.index ["anti_habit_id"], name: "index_anti_habit_tags_on_anti_habit_id"
    t.index ["tag_id"], name: "index_anti_habit_tags_on_tag_id"
  end

  create_table "anti_habits", force: :cascade do |t|
    t.string "title"
    t.text "description"
    t.bigint "user_id", null: false
    t.datetime "created_at", null: false
    t.datetime "updated_at", null: false
    t.integer "comments_count", default: 0, null: false
    t.boolean "is_public", default: true, null: false
    t.index ["user_id"], name: "index_anti_habits_on_user_id"
  end

  create_table "comments", force: :cascade do |t|
    t.bigint "anti_habit_id", null: false
    t.bigint "user_id", null: false
    t.text "body", null: false
    t.datetime "created_at", null: false
    t.datetime "updated_at", null: false
    t.index ["anti_habit_id"], name: "index_comments_on_anti_habit_id"
    t.index ["user_id"], name: "index_comments_on_user_id"
  end

  create_table "notification_settings", force: :cascade do |t|
    t.time "notification_time"
    t.boolean "notify_on_reaction"
    t.boolean "notify_on_comment"
    t.bigint "anti_habit_id", null: false
    t.datetime "created_at", null: false
    t.datetime "updated_at", null: false
    t.boolean "notification_enabled", default: false
    t.index ["anti_habit_id"], name: "index_notification_settings_on_anti_habit_id"
  end

  create_table "reactions", force: :cascade do |t|
    t.integer "reaction_kind", default: 0
    t.bigint "anti_habit_id", null: false
    t.bigint "user_id", null: false
    t.datetime "created_at", null: false
    t.datetime "updated_at", null: false
    t.index ["anti_habit_id"], name: "index_reactions_on_anti_habit_id"
    t.index ["user_id"], name: "index_reactions_on_user_id"
  end

  create_table "tags", force: :cascade do |t|
    t.string "name"
    t.datetime "created_at", null: false
    t.datetime "updated_at", null: false
    t.index ["name"], name: "index_tags_on_name"
  end

  create_table "users", force: :cascade do |t|
    t.string "email", default: "", null: false
    t.string "encrypted_password", default: "", null: false
    t.string "name", default: "", null: false
    t.string "reset_password_token"
    t.datetime "reset_password_sent_at"
    t.datetime "remember_created_at"
    t.datetime "created_at", null: false
    t.datetime "updated_at", null: false
    t.string "provider"
    t.string "uid"
    t.index ["email"], name: "index_users_on_email", unique: true
    t.index ["reset_password_token"], name: "index_users_on_reset_password_token", unique: true
  end

  add_foreign_key "anti_habit_records", "anti_habits"
  add_foreign_key "anti_habit_tags", "anti_habits"
  add_foreign_key "anti_habit_tags", "tags"
  add_foreign_key "anti_habits", "users"
  add_foreign_key "comments", "anti_habits"
  add_foreign_key "comments", "users"
  add_foreign_key "notification_settings", "anti_habits"
  add_foreign_key "reactions", "anti_habits"
  add_foreign_key "reactions", "users"
end
