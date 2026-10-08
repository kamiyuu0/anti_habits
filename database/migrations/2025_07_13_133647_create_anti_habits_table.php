<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('anti_habits')) {
            return;
        }

        Schema::create('anti_habits', function (Blueprint $table) {
            $table->id();
            $table->addColumn('string', 'title')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at', 6);
            $table->timestamp('updated_at', 6);
            $table->integer('comments_count')->default(0);
            $table->boolean('is_public')->default(true);
            $table->integer('goal_days')->nullable();
            $table->boolean('goal_achieved')->default(false);

            $table->index('user_id', 'index_anti_habits_on_user_id');
            $table->foreign('user_id', rails_fk_name('anti_habits', 'user_id'))->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anti_habits');
    }
};
