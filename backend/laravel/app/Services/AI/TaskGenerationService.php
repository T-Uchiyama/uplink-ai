<?php

namespace App\Services\AI;

use App\Models\AiExecutionLog;
use App\Models\GeneratedTask;
use App\Models\TaskGenerationRequest;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TaskGenerationService
{
    public function __construct(
        private OpenAiTaskGenerator $openAiTaskGenerator
    ) {
    }

    public function generate(TaskGenerationRequest $request): void
    {
        $payload = [
            'goal' => $request->goal,
            'available_hours' => (float) $request->available_hours,
            'previous_score' => $request->previous_score,
            'note' => $request->note,
        ];

        $request->update([
            'input_snapshot' => $payload,
        ]);

        $generated = $this->openAiTaskGenerator->generate($payload);

        $result = $generated['result'] ?? null;
        $meta = $generated['meta'] ?? null;
        $tasks = $result['tasks'] ?? null;

        if (!is_array($tasks) || count($tasks) === 0) {
            throw new RuntimeException('No tasks were generated.');
        }

        DB::transaction(function () use ($request, $tasks, $meta): void {
            $nextVersion = $this->resolveNextGenerationVersion($request);

            foreach ($tasks as $index => $task) {
                GeneratedTask::create([
                    'task_generation_request_id' => $request->id,
                    'member_id' => $request->member_id,
                    'generation_version' => $nextVersion,
                    'title' => $task['title'],
                    'description' => $task['description'],
                    'task_category' => $task['task_category'],
                    'priority' => $task['priority'],
                    'difficulty' => $task['difficulty'],
                    'estimated_hours' => $task['estimated_hours'],
                    'status' => 'pending',
                    'sequence_no' => $task['sequence_no'] ?? ($index + 1),
                    'ai_reason' => $task['ai_reason'],
                ]);
            }

            $this->storeAiExecutionLog($request, is_array($meta) ? $meta : []);

            $request->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        });
    }

    private function resolveNextGenerationVersion(TaskGenerationRequest $request): int
    {
        $maxVersion = GeneratedTask::query()
            ->where('member_id', $request->member_id)
            ->max('generation_version');

        $nextVersion = ((int) $maxVersion) + 1;

        return $nextVersion > 0 ? $nextVersion : 1;
    }

    private function storeAiExecutionLog(TaskGenerationRequest $request, array $meta): void
    {
        AiExecutionLog::create([
            'request_id' => $request->id,
            'provider' => $meta['provider'] ?? 'openai',
            'model' => $meta['model'] ?? config('services.openai.model'),
            'prompt_version' => $meta['prompt_version'] ?? 'v1',
            'execution_type' => $meta['execution_type'] ?? 'task_generation',
            'retry_count' => $meta['retry_count'] ?? 0,
            'request_payload' => $meta['request_payload'] ?? null,
            'response_payload' => $meta['response_payload'] ?? null,
            'raw_response' => $meta['raw_response'] ?? null,
            'status' => $meta['status'] ?? 'completed',
            'error_message' => $meta['error_message'] ?? null,
            'latency_ms' => $meta['latency_ms'] ?? null,
            'prompt_tokens' => $meta['prompt_tokens'] ?? null,
            'completion_tokens' => $meta['completion_tokens'] ?? null,
            'total_tokens' => $meta['total_tokens'] ?? null,
            'estimated_cost_usd' => $meta['estimated_cost_usd'] ?? null,
            'executed_at' => $meta['executed_at'] ?? now(),
        ]);
    }
}
