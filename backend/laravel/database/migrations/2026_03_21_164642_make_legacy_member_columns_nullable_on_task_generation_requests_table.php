<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_generation_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('member_id')->nullable()->change();
            $table->unsignedBigInteger('requested_by_member_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('task_generation_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('member_id')->nullable(false)->change();
            $table->unsignedBigInteger('requested_by_member_id')->nullable(false)->change();
        });
    }
};
