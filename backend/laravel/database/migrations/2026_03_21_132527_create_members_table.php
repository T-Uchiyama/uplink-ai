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
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            $table->string('member_code')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            $table->foreignId('upline_member_id')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();

            $table->string('rank_name')->nullable();
            $table->string('role')->default('member');
            $table->string('status')->default('active');

            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();

            $table->timestamps();

            $table->index('upline_member_id');
            $table->index('role');
            $table->index('status');
            $table->index('joined_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
