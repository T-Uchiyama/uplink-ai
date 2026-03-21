<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ai_execution_logs', function (Blueprint $table) {
            if (Schema::hasColumn('ai_execution_logs', 'request_id')) {
                $table->dropColumn('request_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_execution_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_execution_logs', 'request_id')) {
                $table->unsignedBigInteger('request_id')->nullable()->after('id');
            }
        });
    }
};
