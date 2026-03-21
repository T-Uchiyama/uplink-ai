<?php

namespace App\Jobs;

use App\Models\TaskGenerationRequest;
use App\Services\Ai\TaskGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly int $taskGenerationRequestId
    ) {
        $this->onQueue('ai-generation');
    }

    public function handle(TaskGenerationService $taskGenerationService): void
    {
        $taskRequest = TaskGenerationRequest::findOrFail($this->taskGenerationRequestId);

        $taskGenerationService->execute($taskRequest);
    }

    public function failed(\Throwable $exception): void
    {
        $taskRequest = \App\Models\TaskGenerationRequest::find($this->taskGenerationRequestId);

        if (! $taskRequest) {
            return;
        }

        $taskRequest->aiExecutionLogs()->create([
            'provider' => 'openai',
            'model' => config('services.openai.model'),
            'prompt_version' => 1,
            'execution_type' => 'task_generation',
            'retry_count' => $this->attempts(),
            'request_payload' => $taskRequest->input_snapshot ?? [
                    'goal' => $taskRequest->goal,
                    'available_hours' => $taskRequest->available_hours,
                    'previous_score' => $taskRequest->previous_score,
                    'note' => $taskRequest->note,
                ],
            'response_payload' => null,
            'raw_response' => null,
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
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
    }
}
