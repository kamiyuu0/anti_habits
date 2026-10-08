<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('anti_habit_tags')) {
            return;
        }

        Schema::create('anti_habit_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anti_habit_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamp('created_at', 6);
            $table->timestamp('updated_at', 6);

            $table->index('anti_habit_id', 'index_anti_habit_tags_on_anti_habit_id');
            $table->index('tag_id', 'index_anti_habit_tags_on_tag_id');
            $table->foreign('anti_habit_id', rails_fk_name('anti_habit_tags', 'anti_habit_id'))->references('id')->on('anti_habits');
            $table->foreign('tag_id', rails_fk_name('anti_habit_tags', 'tag_id'))->references('id')->on('tags');
        });
        rails_unique_index('anti_habit_tags', ['anti_habit_id', 'tag_id'], 'index_anti_habit_tags_on_anti_habit_id_and_tag_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('anti_habit_tags');
    }
};
