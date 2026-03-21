<?php

namespace App\Jobs;

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

        $taskGenerationService->generate($request);
    }

    public function failed(?Throwable $exception): void
    {
        $request = TaskGenerationRequest::query()->find($this->taskGenerationRequestId);

        if ($request) {
            $request->update([
                'status' => 'failed',
            ]);
        }
    }
}
