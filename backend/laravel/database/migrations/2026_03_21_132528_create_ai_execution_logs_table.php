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
        Schema::create('ai_execution_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('request_id')
                ->constrained('task_generation_requests')
                ->cascadeOnDelete();

            $table->string('provider');
            $table->string('model');
            $table->string('prompt_version')->nullable();
            $table->string('execution_type')->nullable();

            $table->unsignedInteger('retry_count')->default(0);

            $table->jsonb('request_payload')->nullable();
            $table->jsonb('response_payload')->nullable();

            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();

            $table->unsignedInteger('latency_ms')->nullable();

            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();

            $table->decimal('estimated_cost_usd', 12, 6)->nullable();

            $table->timestamp('executed_at')->nullable();

            $table->timestamps();

            $table->index('request_id');
            $table->index('provider');
            $table->index('model');
            $table->index('status');
            $table->index('execution_type');
            $table->index('executed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_execution_logs');
    }
};
