<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_execution_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('task_generation_request_id')->nullable()->after('id');

            $table->foreign('task_generation_request_id')
                ->references('id')
                ->on('task_generation_requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ai_execution_logs', function (Blueprint $table) {
            $table->dropForeign(['task_generation_request_id']);
            $table->dropColumn('task_generation_request_id');
        });
    }
};
