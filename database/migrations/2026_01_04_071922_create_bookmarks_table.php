<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookmarks')) {
            return;
        }

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('anti_habit_id');
            $table->timestamp('created_at', 6);
            $table->timestamp('updated_at', 6);

            $table->index('anti_habit_id', 'index_bookmarks_on_anti_habit_id');
            $table->index('user_id', 'index_bookmarks_on_user_id');
            $table->foreign('anti_habit_id', rails_fk_name('bookmarks', 'anti_habit_id'))->references('id')->on('anti_habits');
            $table->foreign('user_id', rails_fk_name('bookmarks', 'user_id'))->references('id')->on('users');
        });
        rails_unique_index('bookmarks', ['user_id', 'anti_habit_id'], 'index_bookmarks_on_user_id_and_anti_habit_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
