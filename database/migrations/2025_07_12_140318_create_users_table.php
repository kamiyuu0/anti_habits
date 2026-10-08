<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rails 版 (Devise) と同一スキーマの users テーブル。
 * 既存 DB に対して実行した場合はテーブルが存在するため何もしない。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->addColumn('string', 'email')->default('');
            $table->addColumn('string', 'encrypted_password')->default('');
            $table->addColumn('string', 'name')->default('');
            $table->addColumn('string', 'reset_password_token')->nullable();
            $table->timestamp('reset_password_sent_at', 6)->nullable();
            $table->timestamp('remember_created_at', 6)->nullable();
            $table->timestamp('created_at', 6);
            $table->timestamp('updated_at', 6);
            $table->addColumn('string', 'provider')->nullable();
            $table->addColumn('string', 'uid')->nullable();

        });
        rails_unique_index('users', 'email', 'index_users_on_email');
        rails_unique_index('users', 'reset_password_token', 'index_users_on_reset_password_token');
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
