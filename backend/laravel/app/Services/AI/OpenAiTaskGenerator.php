<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use RuntimeException;

class OpenAiTaskGenerator
{
    public function generate(array $input): array
    {
        $model = config('services.openai.model');
        $apiKey = config('services.openai.api_key');
        $baseUrl = rtrim(config('services.openai.base_url'), '/');
        $timeout = config('services.openai.timeout', 120);

        $schema = [
            'name' => 'generated_tasks_response',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['tasks'],
                'properties' => [
                    'tasks' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
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
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'description' => ['type' => 'string'],
                                'task_category' => [
                                    'type' => 'string',
                                    'enum' => ['research', 'planning', 'execution', 'review', 'learning', 'other'],
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
                        ],
                    ],
                ],
            ],
        ];

        $systemPrompt = <<<PROMPT
You are an expert productivity planner.
Generate practical, realistic action tasks based on the user's goal.

Rules:
- Return 3 to 5 tasks.
- Tasks must be concrete and executable.
- sequence_no must start from 1 and increase without gaps.
- estimated_hours must be realistic and positive.
- Respect available_hours if provided.
- previous_score indicates the user's recent performance level.
- note may contain constraints or additional context.
- Output must strictly follow the JSON schema.
PROMPT;

        $userPayload = [
            'goal' => $input['goal'] ?? '',
            'available_hours' => $input['available_hours'] ?? null,
            'previous_score' => $input['previous_score'] ?? null,
            'note' => $input['note'] ?? '',
        ];

        $requestPayload = [
            'model' => $model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $systemPrompt,
                        ],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => json_encode($userPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                        ],
                    ],
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => $schema['name'],
                    'strict' => $schema['strict'],
                    'schema' => $schema['schema'],
                ],
            ],
        ];

        $startedAt = microtime(true);

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->withToken($apiKey)
            ->post($baseUrl . '/responses', $requestPayload);

        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        $responsePayload = $response->json();
        $rawResponse = $response->body();

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI API request failed: ' . ($responsePayload['error']['message'] ?? $response->body())
            );
        }

        $structuredText = $this->extractStructuredJsonText($responsePayload);

        if (!$structuredText) {
            throw new RuntimeException('Structured output text was empty.');
        }

        $decoded = json_decode($structuredText, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Structured output JSON decode failed.');
        }

        return [
            'tasks' => $decoded['tasks'] ?? [],
            'meta' => [
                'provider' => 'openai',
                'model' => $responsePayload['model'] ?? $model,
                'prompt_version' => 'v1',
                'execution_type' => 'task_generation',
                'request_payload' => $requestPayload,
                'response_payload' => $responsePayload,
                'raw_response' => $rawResponse,
                'latency_ms' => $latencyMs,
                'prompt_tokens' => Arr::get($responsePayload, 'usage.input_tokens'),
                'completion_tokens' => Arr::get($responsePayload, 'usage.output_tokens'),
                'total_tokens' => Arr::get($responsePayload, 'usage.total_tokens'),
                'status' => 'success',
                'error_message' => null,
                'executed_at' => now(),
            ],
        ];
    }

    private function extractStructuredJsonText(array $responsePayload): ?string
    {
        if (!empty($responsePayload['output_text']) && is_string($responsePayload['output_text'])) {
            return $responsePayload['output_text'];
        }

        $output = $responsePayload['output'] ?? [];

        foreach ($output as $item) {
            $contents = $item['content'] ?? [];
            foreach ($contents as $content) {
                if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                    return $content['text'];
                }
            }
        }

        return null;
    }
}
