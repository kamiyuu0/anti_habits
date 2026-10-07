<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reactions')) {
            return;
        }

        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->integer('reaction_kind')->nullable()->default(0);
            $table->unsignedBigInteger('anti_habit_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at', 6);
            $table->timestamp('updated_at', 6);

            $table->index('anti_habit_id', 'index_reactions_on_anti_habit_id');
            $table->index('user_id', 'index_reactions_on_user_id');
            $table->foreign('anti_habit_id', rails_fk_name('reactions', 'anti_habit_id'))->references('id')->on('anti_habits');
            $table->foreign('user_id', rails_fk_name('reactions', 'user_id'))->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
