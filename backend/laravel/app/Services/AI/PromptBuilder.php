<?php

namespace App\Services\AI;

use App\Models\TaskGenerationRequest;

class PromptBuilder
{
    public function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
あなたはMLM組織向けの行動支援AIです。
ユーザーの目標、使える時間、過去スコア、メモなどをもとに、
具体的かつ実行可能なタスクを優先順位つきで生成してください。

ルール:
- 出力は必ずJSONのみ
- tasks配列を返す
- 各タスクは sequence_no の昇順で並べる
- 1タスクあたりの estimated_hours は現実的な値にする
- 曖昧な表現を避ける
- task_category, priority, difficulty, ai_reason を必ず含める
- 日本語で出力する
PROMPT;
    }

    public function buildUserPrompt(TaskGenerationRequest $request): string
    {
        $payload = [
            'goal' => $request->goal,
            'available_hours' => $request->available_hours,
            'previous_score' => $request->previous_score,
            'note' => $request->note,
            'input_snapshot' => $request->input_snapshot,
        ];

        return <<<PROMPT
以下の入力情報をもとに、実行可能なタスク一覧を生成してください。

入力:
{$this->toPrettyJson($payload)}

出力形式:
{
  "tasks": [
    {
      "title": "",
      "description": "",
      "task_category": "",
      "priority": "",
      "difficulty": "",
      "estimated_hours": 1,
      "sequence_no": 1,
      "ai_reason": ""
    }
  ]
}
PROMPT;
    }

    public function build(TaskGenerationRequest $request): array
    {
        return [
            'system' => $this->buildSystemPrompt(),
            'user' => $this->buildUserPrompt($request),
        ];
    }

    private function toPrettyJson(array $payload): string
    {
        return json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }
}
