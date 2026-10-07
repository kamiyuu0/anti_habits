<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('anti_habit_records')) {
            return;
        }

        Schema::create('anti_habit_records', function (Blueprint $table) {
            $table->id();
            $table->date('recorded_on')->nullable();
            $table->unsignedBigInteger('anti_habit_id');
            $table->timestamp('created_at', 6);
            $table->timestamp('updated_at', 6);

            $table->index('anti_habit_id', 'index_anti_habit_records_on_anti_habit_id');
            $table->foreign('anti_habit_id', rails_fk_name('anti_habit_records', 'anti_habit_id'))->references('id')->on('anti_habits');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anti_habit_records');
    }
};
