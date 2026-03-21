<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notion_sync_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('generated_task_id')
                ->constrained('generated_tasks')
                ->cascadeOnDelete();

            $table->string('notion_page_id')->nullable();
            $table->string('sync_type');
            $table->string('status')->default('pending');

            $table->jsonb('request_payload')->nullable();
            $table->jsonb('response_payload')->nullable();

            $table->text('error_message')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            $table->index('generated_task_id');
            $table->index('notion_page_id');
            $table->index('sync_type');
            $table->index('status');
            $table->index('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notion_sync_logs');
    }
};
