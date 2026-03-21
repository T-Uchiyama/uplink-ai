<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_generation_requests', function (Blueprint $table) {
            $table->integer('generation_version')
                ->default(1)
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('task_generation_requests', function (Blueprint $table) {
            $table->dropColumn('generation_version');
        });
    }
};
