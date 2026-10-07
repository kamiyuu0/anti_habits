<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_settings')) {
            return;
        }

        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            // Rails と同じく UTC の時刻を保存する (精度指定なし)
            $table->addColumn('time', 'notification_time', ['precision' => null]);
            $table->boolean('notify_on_reaction')->default(false);
            $table->boolean('notify_on_comment')->default(false);
            $table->unsignedBigInteger('anti_habit_id');
            $table->timestamp('created_at', 6);
            $table->timestamp('updated_at', 6);
            $table->boolean('notification_enabled')->default(false);

            $table->index('anti_habit_id', 'index_notification_settings_on_anti_habit_id');
            $table->foreign('anti_habit_id', rails_fk_name('notification_settings', 'anti_habit_id'))->references('id')->on('anti_habits');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
