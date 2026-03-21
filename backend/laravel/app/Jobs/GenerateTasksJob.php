<?php

namespace App\Jobs;

use App\Models\AiExecutionLog;
use App\Models\TaskGenerationRequest;
use App\Services\AI\TaskGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateTasksJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $taskGenerationRequestId
    ) {
        $this->onQueue('ai-generation');
    }

    public function handle(TaskGenerationService $taskGenerationService): void
    {
        $request = TaskGenerationRequest::query()->findOrFail($this->taskGenerationRequestId);

        $request->update([
            'status' => 'processing',
        ]);

        try {
            $taskGenerationService->generate($request);
        } catch (Throwable $e) {
            $request->update([
                'status' => 'failed',
            ]);

            $this->createFailedLog($request, $e);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $request = TaskGenerationRequest::query()->find($this->taskGenerationRequestId);

        if (!$request) {
            return;
        }

        if ($request->status !== 'failed') {
            $request->update([
                'status' => 'failed',
            ]);
        }

        $alreadyLogged = AiExecutionLog::query()
            ->where('request_id', $request->id)
            ->where('status', 'failed')
            ->exists();

        if ($alreadyLogged) {
            return;
        }

        $this->createFailedLog(
            $request,
            $exception ?? new \RuntimeException('GenerateTasksJob failed without exception.')
        );
    }

    private function createFailedLog(TaskGenerationRequest $request, Throwable $e): void
    {
        AiExecutionLog::create([
            'request_id' => $request->id,
            'provider' => 'openai',
            'model' => config('services.openai.model'),
            'prompt_version' => 'v1',
            'execution_type' => 'task_generation',
            'retry_count' => method_exists($this, 'attempts') ? max($this->attempts() - 1, 0) : 0,
            'request_payload' => [
                'input' => [
                    'goal' => $request->goal,
                    'available_hours' => (float) $request->available_hours,
                    'previous_score' => $request->previous_score,
                    'note' => $request->note,
                ],
            ],
            'response_payload' => null,
            'raw_response' => null,
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'latency_ms' => null,
            'prompt_tokens' => null,
            'completion_tokens' => null,
            'total_tokens' => null,
            'estimated_cost_usd' => null,
            'executed_at' => now(),
        ]);
    }
}
