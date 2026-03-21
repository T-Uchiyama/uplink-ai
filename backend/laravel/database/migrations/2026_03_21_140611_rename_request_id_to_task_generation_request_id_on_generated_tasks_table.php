<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 既存の外部キー制約がある可能性を考慮して先にDROP
        // PostgreSQLでは制約名が generated_tasks_request_id_foreign になっている想定
        try {
            DB::statement('ALTER TABLE generated_tasks DROP CONSTRAINT IF EXISTS generated_tasks_request_id_foreign');
        } catch (\Throwable $e) {
            // 何もしない
        }

        Schema::table('generated_tasks', function (Blueprint $table) {
            $table->renameColumn('request_id', 'task_generation_request_id');
        });

        // 外部キーを張り直す
        DB::statement('
            ALTER TABLE generated_tasks
            ADD CONSTRAINT generated_tasks_task_generation_request_id_foreign
            FOREIGN KEY (task_generation_request_id)
            REFERENCES task_generation_requests(id)
            ON DELETE CASCADE
        ');
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE generated_tasks DROP CONSTRAINT IF EXISTS generated_tasks_task_generation_request_id_foreign');
        } catch (\Throwable $e) {
            // 何もしない
        }

        Schema::table('generated_tasks', function (Blueprint $table) {
            $table->renameColumn('task_generation_request_id', 'request_id');
        });

        DB::statement('
            ALTER TABLE generated_tasks
            ADD CONSTRAINT generated_tasks_request_id_foreign
            FOREIGN KEY (request_id)
            REFERENCES task_generation_requests(id)
            ON DELETE CASCADE
        ');
    }
};
