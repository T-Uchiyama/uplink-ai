<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ai_execution_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_execution_logs', 'raw_response')) {
                $table->json('raw_response')->nullable()->after('response_payload');
            }

            if (! Schema::hasColumn('ai_execution_logs', 'latency_ms')) {
                $table->unsignedInteger('latency_ms')->nullable()->after('error_message');
            }

            if (! Schema::hasColumn('ai_execution_logs', 'prompt_tokens')) {
                $table->unsignedInteger('prompt_tokens')->nullable()->after('latency_ms');
            }

            if (! Schema::hasColumn('ai_execution_logs', 'completion_tokens')) {
                $table->unsignedInteger('completion_tokens')->nullable()->after('prompt_tokens');
            }

            if (! Schema::hasColumn('ai_execution_logs', 'total_tokens')) {
                $table->unsignedInteger('total_tokens')->nullable()->after('completion_tokens');
            }

            if (! Schema::hasColumn('ai_execution_logs', 'cost_usd')) {
                $table->decimal('cost_usd', 12, 6)->nullable()->after('total_tokens');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_execution_logs', function (Blueprint $table) {
            $columns = [
                'raw_response',
                'latency_ms',
                'prompt_tokens',
                'completion_tokens',
                'total_tokens',
                'cost_usd',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('ai_execution_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
