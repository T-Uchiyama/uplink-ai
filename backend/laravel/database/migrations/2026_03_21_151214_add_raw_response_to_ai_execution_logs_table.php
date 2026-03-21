<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_execution_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_execution_logs', 'raw_response')) {
                $table->longText('raw_response')->nullable()->after('response_payload');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_execution_logs', function (Blueprint $table) {
            if (Schema::hasColumn('ai_execution_logs', 'raw_response')) {
                $table->dropColumn('raw_response');
            }
        });
    }
};
