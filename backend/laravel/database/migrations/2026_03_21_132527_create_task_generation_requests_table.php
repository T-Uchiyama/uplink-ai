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
        Schema::create('task_generation_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            $table->foreignId('requested_by_member_id')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();

            $table->text('goal');
            $table->decimal('available_hours', 5, 2)->nullable();
            $table->integer('previous_score')->nullable();
            $table->text('note')->nullable();

            $table->jsonb('input_snapshot')->nullable();

            $table->string('status')->default('pending');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index('member_id');
            $table->index('requested_by_member_id');
            $table->index('status');
            $table->index('requested_at');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_generation_requests');
    }
};
