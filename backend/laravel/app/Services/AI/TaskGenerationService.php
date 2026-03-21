<?php

namespace App\Services\Ai;

use App\Models\TaskGenerationRequest;
use App\Models\GeneratedTask;
use App\Models\AiExecutionLog;
use App\Services\Ai\OpenAiTaskGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class TaskGenerationService
{
    public function __construct(
        private OpenAiTaskGenerator $generator,
    ) {}

    public function execute(TaskGenerationRequest $request): void
    {
        $request->update([
            'status' => 'processing',
        ]);

        try {
            $result = $this->generator->generate([
                'goal' => $request->goal,
                'available_hours' => $request->available_hours,
                'previous_score' => $request->previous_score,
                'note' => $request->note,
            ]);

            $tasks = $result['tasks'] ?? [];
            $meta = $result['meta'] ?? [];

            $this->validateTasks($tasks);

            DB::transaction(function () use ($request, $tasks, $meta) {
                foreach ($tasks as $task) {
                    GeneratedTask::create([
                        'task_generation_request_id' => $request->id,
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

                AiExecutionLog::create([
                    'task_generation_request_id' => $request->id,
                    'provider' => $meta['provider'] ?? 'openai',
                    'model' => $meta['model'] ?? null,
                    'prompt_version' => $meta['prompt_version'] ?? 'v1',
                    'execution_type' => $meta['execution_type'] ?? 'task_generation',
                    'request_payload' => $meta['request_payload'] ?? null,
                    'response_payload' => $meta['response_payload'] ?? null,
                    'raw_response' => $meta['raw_response'] ?? null,
                    'latency_ms' => $meta['latency_ms'] ?? null,
                    'prompt_tokens' => $meta['prompt_tokens'] ?? null,
                    'completion_tokens' => $meta['completion_tokens'] ?? null,
                    'total_tokens' => $meta['total_tokens'] ?? null,
                    'status' => 'success',
                    'error_message' => null,
                    'executed_at' => $meta['executed_at'] ?? now(),
                ]);

                $request->update([
                    'status' => 'completed',
                ]);
            });
        } catch (Throwable $e) {
            AiExecutionLog::create([
                'task_generation_request_id' => $request->id,
                'provider' => 'openai',
                'model' => config('services.openai.model'),
                'prompt_version' => 'v1',
                'execution_type' => 'task_generation',
                'request_payload' => [
                    'goal' => $request->goal,
                    'available_hours' => $request->available_hours,
                    'previous_score' => $request->previous_score,
                    'note' => $request->note,
                ],
                'response_payload' => null,
                'raw_response' => null,
                'latency_ms' => null,
                'prompt_tokens' => null,
                'completion_tokens' => null,
                'total_tokens' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'executed_at' => now(),
            ]);

            $request->update([
                'status' => 'failed',
            ]);

            throw $e;
        }
    }

    private function validateTasks(array $tasks): void
    {
        if (empty($tasks)) {
            throw new RuntimeException('Generated tasks were empty.');
        }

        $validator = Validator::make(
            ['tasks' => $tasks],
            [
                'tasks' => ['required', 'array', 'min:1', 'max:5'],
                'tasks.*.title' => ['required', 'string', 'max:255'],
                'tasks.*.description' => ['required', 'string'],
                'tasks.*.task_category' => ['required', 'in:research,planning,execution,review,learning,other'],
                'tasks.*.priority' => ['required', 'in:low,medium,high'],
                'tasks.*.difficulty' => ['required', 'in:easy,medium,hard'],
                'tasks.*.estimated_hours' => ['required', 'numeric', 'min:0.5', 'max:100'],
                'tasks.*.sequence_no' => ['required', 'integer', 'min:1'],
                'tasks.*.ai_reason' => ['required', 'string'],
            ]
        );

        if ($validator->fails()) {
            throw new RuntimeException(
                'Generated task validation failed: ' . $validator->errors()->toJson(JSON_UNESCAPED_UNICODE)
            );
        }

        $sequenceNos = collect($tasks)->pluck('sequence_no')->sort()->values()->all();
        $expected = range(1, count($sequenceNos));

        if ($sequenceNos !== $expected) {
            throw new RuntimeException('sequence_no must start at 1 and increase without gaps.');
        }
    }
}
