<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiTaskGenerator
{
    public function generate(array $payload): array
    {
        $provider = 'openai';
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model');
        $timeout = config('services.openai.timeout', 60);
        $maxOutputTokens = config('services.openai.max_output_tokens', 2000);

        if (empty($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $promptVersion = 'v1';
        $executionType = 'task_generation';

        $schema = [
            'name' => 'task_generation_response',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'tasks' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'description' => ['type' => 'string'],
                                'task_category' => [
                                    'type' => 'string',
                                    'enum' => ['planning', 'execution', 'review', 'learning', 'communication', 'analysis'],
                                ],
                                'priority' => [
                                    'type' => 'string',
                                    'enum' => ['low', 'medium', 'high'],
                                ],
                                'difficulty' => [
                                    'type' => 'string',
                                    'enum' => ['easy', 'medium', 'hard'],
                                ],
                                'estimated_hours' => ['type' => 'number'],
                                'sequence_no' => ['type' => 'integer'],
                                'ai_reason' => ['type' => 'string'],
                            ],
                            'required' => [
                                'title',
                                'description',
                                'task_category',
                                'priority',
                                'difficulty',
                                'estimated_hours',
                                'sequence_no',
                                'ai_reason',
                            ],
                        ],
                    ],
                ],
                'required' => ['tasks'],
            ],
        ];

        $systemPrompt = <<<'PROMPT'
あなたは、実行可能なタスク設計に特化した優秀な業務設計AIです。
ユーザーの目標・可処分時間・過去スコア・メモをもとに、
実行順序が明確で、現実的で、過不足のないタスクを3〜7件生成してください。
PROMPT;

        $userPrompt = json_encode([
            'goal' => $payload['goal'] ?? '',
            'available_hours' => (float) ($payload['available_hours'] ?? 0),
            'previous_score' => isset($payload['previous_score']) ? (int) $payload['previous_score'] : null,
            'note' => $payload['note'] ?? '',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $requestBody = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => $schema,
            ],
            'temperature' => 0.7,
            'max_completion_tokens' => $maxOutputTokens,
        ];

        $start = microtime(true);

        $response = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', $requestBody);

        $latencyMs = (int) round((microtime(true) - $start) * 1000);

        $rawResponse = $response->body();

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI API request failed: ' . $response->status() . ' ' . $rawResponse
            );
        }

        $json = $response->json();

        $content = data_get($json, 'choices.0.message.content');

        if (!is_string($content) || $content === '') {
            throw new RuntimeException('OpenAI response content is empty.');
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded) || !isset($decoded['tasks'])) {
            throw new RuntimeException('OpenAI response invalid.');
        }

        // 🔥 usage取得
        $usage = $json['usage'] ?? [];

        return [
            'result' => $decoded,
            'meta' => [
                'provider' => $provider,
                'model' => $model,
                'prompt_version' => $promptVersion,
                'execution_type' => $executionType,
                'retry_count' => 0,

                'request_payload' => [
                    'input' => $payload,
                    'system_prompt' => $systemPrompt,
                    'user_prompt' => $userPrompt,
                    'openai_request' => $requestBody,
                ],

                'response_payload' => $json,
                'raw_response' => $rawResponse,

                'status' => 'completed',
                'error_message' => null,

                'latency_ms' => $latencyMs,

                'prompt_tokens' => $usage['prompt_tokens'] ?? null,
                'completion_tokens' => $usage['completion_tokens'] ?? null,
                'total_tokens' => $usage['total_tokens'] ?? null,

                'estimated_cost_usd' => null,

                'executed_at' => now(),
            ],
        ];
    }
}
