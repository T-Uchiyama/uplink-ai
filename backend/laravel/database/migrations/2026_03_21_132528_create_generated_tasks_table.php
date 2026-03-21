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
        Schema::create('generated_tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('request_id')
                ->constrained('task_generation_requests')
                ->cascadeOnDelete();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            $table->unsignedInteger('generation_version')->default(1);

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('task_category')->nullable();
            $table->string('priority')->nullable();
            $table->string('difficulty')->nullable();

            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();

            $table->decimal('estimated_hours', 5, 2)->nullable();

            $table->string('status')->default('pending');
            $table->unsignedInteger('sequence_no')->default(1);

            $table->text('ai_reason')->nullable();

            $table->string('notion_page_id')->nullable();
            $table->timestamp('synced_to_notion_at')->nullable();

            $table->timestamps();

            $table->index('request_id');
            $table->index('member_id');
            $table->index('generation_version');
            $table->index('status');
            $table->index('sequence_no');
            $table->index('due_date');
            $table->index('synced_to_notion_at');

            $table->unique(['request_id', 'generation_version', 'sequence_no'], 'generated_tasks_request_version_sequence_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_tasks');
    }
};
