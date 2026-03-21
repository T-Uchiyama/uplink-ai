<?php

namespace App\Services\AI;

use App\Models\AiExecutionLog;
use App\Models\GeneratedTask;
use App\Models\TaskGenerationRequest;
use Illuminate\Support\Facades\DB;
use Throwable;

class TaskGenerationService
{
    public function __construct(
        private readonly PromptBuilder $promptBuilder
    ) {
    }

    public function generate(TaskGenerationRequest $request): array
    {
        $request->update([
            'status' => 'processing',
        ]);

        $prompts = $this->promptBuilder->build($request);

        $log = AiExecutionLog::create([
            'request_id' => $request->id,
            'provider' => 'openai',
            'model' => 'stub-model',
            'prompt_version' => 'v1',
            'execution_type' => 'task_generation',
            'retry_count' => 0,
            'request_payload' => [
                'system_prompt' => $prompts['system'],
                'user_prompt' => $prompts['user'],
            ],
            'status' => 'processing',
            'executed_at' => now(),
        ]);

        try {
            $response = $this->fakeAiResponse($request);

            DB::transaction(function () use ($request, $response, $log) {
                $generationVersion = $this->resolveNextGenerationVersion($request);

                foreach ($response['tasks'] as $task) {
                    GeneratedTask::create([
                        'task_generation_request_id' => $request->id,
                        'member_id' => $request->member_id,
                        'generation_version' => $generationVersion,
                        'title' => $task['title'],
                        'description' => $task['description'] ?? null,
                        'task_category' => $task['task_category'] ?? null,
                        'priority' => $task['priority'] ?? null,
                        'difficulty' => $task['difficulty'] ?? null,
                        'estimated_hours' => $task['estimated_hours'] ?? null,
                        'status' => 'pending',
                        'sequence_no' => $task['sequence_no'],
                        'ai_reason' => $task['ai_reason'] ?? null,
                    ]);
                }

                $log->update([
                    'response_payload' => $response,
                    'status' => 'success',
                ]);

                $request->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            });

            return $response;
        } catch (Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $request->update([
                'status' => 'failed',
            ]);

            throw $e;
        }
    }

    private function resolveNextGenerationVersion(TaskGenerationRequest $request): int
    {
        $maxVersion = GeneratedTask::query()
            ->where('task_generation_request_id', $request->id)
            ->max('generation_version');

        return $maxVersion ? $maxVersion + 1 : 1;
    }

    private function fakeAiResponse(TaskGenerationRequest $request): array
    {
        $goal = $request->goal;

        return [
            'tasks' => [
                [
                    'title' => '目標達成に必要なアプローチ候補を3件書き出す',
                    'description' => "目標「{$goal}」に対して、有効と思われる行動パターンを洗い出す。",
                    'task_category' => 'planning',
                    'priority' => 'high',
                    'difficulty' => 'easy',
                    'estimated_hours' => 1.0,
                    'sequence_no' => 1,
                    'ai_reason' => '最初に行動方針を明確化することで、後続タスクの精度が上がるため。',
                ],
                [
                    'title' => '今週実行する具体的行動を5件に分解する',
                    'description' => '期限と実行条件が明確な行動単位まで落とし込む。',
                    'task_category' => 'execution',
                    'priority' => 'high',
                    'difficulty' => 'medium',
                    'estimated_hours' => 2.0,
                    'sequence_no' => 2,
                    'ai_reason' => '抽象目標を実行可能タスクへ分解することで着手しやすくなるため。',
                ],
                [
                    'title' => '進捗確認用の振り返りメモを作成する',
                    'description' => '達成率、反応、課題を記録するテンプレートを用意する。',
                    'task_category' => 'review',
                    'priority' => 'medium',
                    'difficulty' => 'easy',
                    'estimated_hours' => 0.5,
                    'sequence_no' => 3,
                    'ai_reason' => '継続改善に必要な情報を残し、再生成時の精度向上にもつながるため。',
                ],
            ],
        ];
    }
}
