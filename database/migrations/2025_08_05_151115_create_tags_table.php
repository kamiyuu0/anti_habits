<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tags')) {
            return;
        }

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->addColumn('string', 'name')->nullable();
            $table->timestamp('created_at', 6);
            $table->timestamp('updated_at', 6);

            $table->index('name', 'index_tags_on_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
