<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('comments')) {
            return;
        }

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anti_habit_id');
            $table->unsignedBigInteger('user_id');
            $table->text('body');
            $table->timestamp('created_at', 6);
            $table->timestamp('updated_at', 6);

            $table->index('anti_habit_id', 'index_comments_on_anti_habit_id');
            $table->index('user_id', 'index_comments_on_user_id');
            $table->foreign('anti_habit_id', rails_fk_name('comments', 'anti_habit_id'))->references('id')->on('anti_habits');
            $table->foreign('user_id', rails_fk_name('comments', 'user_id'))->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
