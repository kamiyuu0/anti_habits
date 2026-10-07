<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rails 版で使用していた Solid Queue のテーブル。
 * Laravel 版では使用しないが、DB スキーマを Rails 版と揃えるために定義している。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('solid_queue_jobs')) {
            return;
        }

        Schema::create('solid_queue_blocked_executions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('job_id');
            $table->addColumn('string', 'queue_name');
            $table->integer('priority')->default(0);
            $table->addColumn('string', 'concurrency_key');
            $table->timestamp('expires_at', 6);
            $table->timestamp('created_at', 6);
            $table->index(['concurrency_key', 'priority', 'job_id'], 'index_solid_queue_blocked_executions_for_release');
            $table->index(['expires_at', 'concurrency_key'], 'index_solid_queue_blocked_executions_for_maintenance');
        });
        rails_unique_index('solid_queue_blocked_executions', 'job_id', 'index_solid_queue_blocked_executions_on_job_id');

        Schema::create('solid_queue_claimed_executions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('job_id');
            $table->bigInteger('process_id')->nullable();
            $table->timestamp('created_at', 6);
            $table->index(['process_id', 'job_id'], 'index_solid_queue_claimed_executions_on_process_id_and_job_id');
        });
        rails_unique_index('solid_queue_claimed_executions', 'job_id', 'index_solid_queue_claimed_executions_on_job_id');

        Schema::create('solid_queue_failed_executions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('job_id');
            $table->text('error')->nullable();
            $table->timestamp('created_at', 6);
        });
        rails_unique_index('solid_queue_failed_executions', 'job_id', 'index_solid_queue_failed_executions_on_job_id');

        Schema::create('solid_queue_jobs', function (Blueprint $table) {
            $table->id();
            $table->addColumn('string', 'queue_name');
            $table->addColumn('string', 'class_name');
            $table->text('arguments')->nullable();
            $table->integer('priority')->default(0);
            $table->addColumn('string', 'active_job_id')->nullable();
            $table->timestamp('scheduled_at', 6)->nullable();
            $table->timestamp('finished_at', 6)->nullable();
            $table->addColumn('string', 'concurrency_key')->nullable();
            $table->timestamp('created_at', 6);
            $table->timestamp('updated_at', 6);
            $table->index('active_job_id', 'index_solid_queue_jobs_on_active_job_id');
            $table->index('class_name', 'index_solid_queue_jobs_on_class_name');
            $table->index('finished_at', 'index_solid_queue_jobs_on_finished_at');
            $table->index(['queue_name', 'finished_at'], 'index_solid_queue_jobs_for_filtering');
            $table->index(['scheduled_at', 'finished_at'], 'index_solid_queue_jobs_for_alerting');
        });

        Schema::create('solid_queue_pauses', function (Blueprint $table) {
            $table->id();
            $table->addColumn('string', 'queue_name');
            $table->timestamp('created_at', 6);
        });
        rails_unique_index('solid_queue_pauses', 'queue_name', 'index_solid_queue_pauses_on_queue_name');

        Schema::create('solid_queue_processes', function (Blueprint $table) {
            $table->id();
            $table->addColumn('string', 'kind');
            $table->timestamp('last_heartbeat_at', 6);
            $table->bigInteger('supervisor_id')->nullable();
            $table->integer('pid');
            $table->addColumn('string', 'hostname')->nullable();
            $table->text('metadata')->nullable();
            $table->timestamp('created_at', 6);
            $table->addColumn('string', 'name');
            $table->index('last_heartbeat_at', 'index_solid_queue_processes_on_last_heartbeat_at');
            $table->index('supervisor_id', 'index_solid_queue_processes_on_supervisor_id');
        });
        rails_unique_index('solid_queue_processes', ['name', 'supervisor_id'], 'index_solid_queue_processes_on_name_and_supervisor_id');

        Schema::create('solid_queue_ready_executions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('job_id');
            $table->addColumn('string', 'queue_name');
            $table->integer('priority')->default(0);
            $table->timestamp('created_at', 6);
            $table->index(['priority', 'job_id'], 'index_solid_queue_poll_all');
            $table->index(['queue_name', 'priority', 'job_id'], 'index_solid_queue_poll_by_queue');
        });
        rails_unique_index('solid_queue_ready_executions', 'job_id', 'index_solid_queue_ready_executions_on_job_id');

        Schema::create('solid_queue_recurring_executions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('job_id');
            $table->addColumn('string', 'task_key');
            $table->timestamp('run_at', 6);
            $table->timestamp('created_at', 6);
        });
        rails_unique_index('solid_queue_recurring_executions', 'job_id', 'index_solid_queue_recurring_executions_on_job_id');
        rails_unique_index('solid_queue_recurring_executions', ['task_key', 'run_at'], 'index_solid_queue_recurring_executions_on_task_key_and_run_at');

        Schema::create('solid_queue_recurring_tasks', function (Blueprint $table) {
            $table->id();
            $table->addColumn('string', 'key');
            $table->addColumn('string', 'schedule');
            $table->string('command', 2048)->nullable();
            $table->addColumn('string', 'class_name')->nullable();
            $table->text('arguments')->nullable();
            $table->addColumn('string', 'queue_name')->nullable();
            $table->integer('priority')->nullable()->default(0);
            $table->boolean('static')->default(true);
            $table->text('description')->nullable();
            $table->timestamp('created_at', 6);
            $table->timestamp('updated_at', 6);
            $table->index('static', 'index_solid_queue_recurring_tasks_on_static');
        });
        rails_unique_index('solid_queue_recurring_tasks', 'key', 'index_solid_queue_recurring_tasks_on_key');

        Schema::create('solid_queue_scheduled_executions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('job_id');
            $table->addColumn('string', 'queue_name');
            $table->integer('priority')->default(0);
            $table->timestamp('scheduled_at', 6);
            $table->timestamp('created_at', 6);
            $table->index(['scheduled_at', 'priority', 'job_id'], 'index_solid_queue_dispatch_all');
        });
        rails_unique_index('solid_queue_scheduled_executions', 'job_id', 'index_solid_queue_scheduled_executions_on_job_id');

        Schema::create('solid_queue_semaphores', function (Blueprint $table) {
            $table->id();
            $table->addColumn('string', 'key');
            $table->integer('value')->default(1);
            $table->timestamp('expires_at', 6);
            $table->timestamp('created_at', 6);
            $table->timestamp('updated_at', 6);
            $table->index('expires_at', 'index_solid_queue_semaphores_on_expires_at');
            $table->index(['key', 'value'], 'index_solid_queue_semaphores_on_key_and_value');
        });
        rails_unique_index('solid_queue_semaphores', 'key', 'index_solid_queue_semaphores_on_key');

        foreach (['blocked', 'claimed', 'failed', 'ready', 'recurring', 'scheduled'] as $kind) {
            $tableName = "solid_queue_{$kind}_executions";
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreign('job_id', rails_fk_name($tableName, 'job_id'))
                    ->references('id')->on('solid_queue_jobs')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['blocked', 'claimed', 'failed', 'ready', 'recurring', 'scheduled'] as $kind) {
            Schema::dropIfExists("solid_queue_{$kind}_executions");
        }
        foreach (['jobs', 'pauses', 'processes', 'recurring_tasks', 'semaphores'] as $name) {
            Schema::dropIfExists("solid_queue_{$name}");
        }
    }
};
