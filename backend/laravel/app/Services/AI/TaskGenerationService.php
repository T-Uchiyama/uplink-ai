<?php

namespace App\Services;

use App\Models\TaskGenerationRequest;
use App\Services\AI\OpenAiTaskGenerator;
use Illuminate\Support\Facades\DB;
use Throwable;

class TaskGenerationService
{
    public function __construct(
        private readonly OpenAiTaskGenerator $openAiTaskGenerator,
    ) {
    }

    public function execute(TaskGenerationRequest $taskRequest): void
    {
        $input = [
            'goal' => $taskRequest->goal,
            'available_hours' => $taskRequest->available_hours,
            'previous_score' => $taskRequest->previous_score,
            'note' => $taskRequest->note,
        ];

        $taskRequest->update([
            'status' => 'processing',
            'input_snapshot' => $input,
        ]);

        try {
            $generated = $this->openAiTaskGenerator->generate($input);

            DB::transaction(function () use ($taskRequest, $generated) {
                $result = $generated['result'];
                $meta = $generated['meta'];

                foreach ($result['tasks'] as $task) {
                    $taskRequest->generatedTasks()->create([
                        'title' => $task['title'],
                        'description' => $task['description'],
                        'task_category' => $task['task_category'],
                        'priority' => $task['priority'],
                        'difficulty' => $task['difficulty'],
                        'estimated_hours' => $task['estimated_hours'],
                        'sequence_no' => $task['sequence_no'],
                        'ai_reason' => $task['ai_reason'],
                    ]);
                }

                $taskRequest->aiExecutionLogs()->create([
                    'provider' => $meta['provider'],
                    'model' => $meta['model'],
                    'prompt_version' => $meta['prompt_version'],
                    'execution_type' => 'task_generation',
                    'retry_count' => 0,
                    'request_payload' => $meta['request_payload'] ?? null,
                    'response_payload' => $result,
                    'raw_response' => $meta['raw_response'] ?? null,
                    'status' => 'success',
                    'error_message' => null,
                    'latency_ms' => $meta['latency_ms'] ?? null,
                    'prompt_tokens' => data_get($meta, 'usage.prompt_tokens'),
                    'completion_tokens' => data_get($meta, 'usage.completion_tokens'),
                    'total_tokens' => data_get($meta, 'usage.total_tokens'),
                    'cost_usd' => null,
                    'executed_at' => now(),
                ]);

                $taskRequest->update([
                    'status' => 'completed',
                ]);
            });
        } catch (Throwable $e) {
            $taskRequest->aiExecutionLogs()->create([
                'provider' => 'openai',
                'model' => config('services.openai.model'),
                'prompt_version' => 1,
                'execution_type' => 'task_generation',
                'retry_count' => 0,
                'request_payload' => $input,
                'response_payload' => null,
                'raw_response' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'latency_ms' => null,
                'prompt_tokens' => null,
                'completion_tokens' => null,
                'total_tokens' => null,
                'cost_usd' => null,
                'executed_at' => now(),
            ]);

            $taskRequest->update([
                'status' => 'failed',
            ]);

            throw $e;
        }
    }
}
