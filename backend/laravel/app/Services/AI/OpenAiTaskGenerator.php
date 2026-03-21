<?php

namespace App\Services\AI;

use App\Exceptions\OpenAiGenerationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAiTaskGenerator
{
    public function generate(array $input): array
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model');
        $timeout = config('services.openai.timeout', 120);
        $maxOutputTokens = config('services.openai.max_output_tokens', 2000);
        $apiUrl = config('services.openai.api_url');

        if (blank($apiKey)) {
            throw new OpenAiGenerationException('OPENAI_API_KEY is not configured.');
        }

        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($input);
        $schema = $this->taskJsonSchema();

        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => $userPrompt,
                ],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'generated_tasks_response',
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
            'max_completion_tokens' => $maxOutputTokens,
        ];

        $started = microtime(true);

        try {
            $response = Http::timeout($timeout)
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->post($apiUrl, $payload);
        } catch (ConnectionException $e) {
            throw new OpenAiGenerationException('Failed to connect to OpenAI API: ' . $e->getMessage(), 0, $e);
        } catch (Throwable $e) {
            throw new OpenAiGenerationException('Unexpected OpenAI request error: ' . $e->getMessage(), 0, $e);
        }

        $latencyMs = (int) round((microtime(true) - $started) * 1000);
        $rawResponse = $response->json();

        if (! $response->successful()) {
            throw new OpenAiGenerationException(
                'OpenAI API error: HTTP ' . $response->status() . ' / ' . json_encode($rawResponse, JSON_UNESCAPED_UNICODE)
            );
        }

        $content = data_get($rawResponse, 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new OpenAiGenerationException(
                'OpenAI response does not contain choices.0.message.content.'
            );
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new OpenAiGenerationException(
                'Failed to decode JSON content from OpenAI response.'
            );
        }

        if (! isset($decoded['tasks']) || ! is_array($decoded['tasks'])) {
            throw new OpenAiGenerationException(
                'OpenAI response JSON does not contain a valid tasks array.'
            );
        }

        $normalizedTasks = $this->normalizeTasks($decoded['tasks']);

        return [
            'result' => [
                'tasks' => $normalizedTasks,
            ],
            'meta' => [
                'provider' => 'openai',
                'model' => $model,
                'prompt_version' => 1,
                'request_payload' => $payload,
                'raw_response' => $rawResponse,
                'latency_ms' => $latencyMs,
                'usage' => [
                    'prompt_tokens' => data_get($rawResponse, 'usage.prompt_tokens'),
                    'completion_tokens' => data_get($rawResponse, 'usage.completion_tokens'),
                    'total_tokens' => data_get($rawResponse, 'usage.total_tokens'),
                ],
            ],
        ];
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert task planning assistant.

Your job is to generate a realistic and actionable task plan for a member based on:
- goal
- available_hours
- previous_score
- note

Rules:
- Output must strictly follow the provided JSON schema.
- Return only valid JSON.
- Create a practical sequence of tasks.
- Keep titles concise and specific.
- Descriptions should be useful and implementation-oriented.
- estimated_hours must be realistic.
- sequence_no must start from 1 and increase sequentially.
- task_category should be one of: learning, execution, review, communication, planning, analysis
- priority should be one of: low, medium, high
- difficulty should be one of: easy, medium, hard
- ai_reason should explain why the task is included.
- Total estimated hours should generally fit within available_hours, but slight overage is acceptable if justified.
PROMPT;
    }

    private function buildUserPrompt(array $input): string
    {
        return json_encode([
            'goal' => $input['goal'] ?? '',
            'available_hours' => $input['available_hours'] ?? null,
            'previous_score' => $input['previous_score'] ?? null,
            'note' => $input['note'] ?? '',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function taskJsonSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['tasks'],
            'properties' => [
                'tasks' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'maxItems' => 20,
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
                            'title' => [
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 120,
                            ],
                            'description' => [
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 1000,
                            ],
                            'task_category' => [
                                'type' => 'string',
                                'enum' => ['learning', 'execution', 'review', 'communication', 'planning', 'analysis'],
                            ],
                            'priority' => [
                                'type' => 'string',
                                'enum' => ['low', 'medium', 'high'],
                            ],
                            'difficulty' => [
                                'type' => 'string',
                                'enum' => ['easy', 'medium', 'hard'],
                            ],
                            'estimated_hours' => [
                                'type' => 'number',
                                'minimum' => 0.5,
                                'maximum' => 100,
                            ],
                            'sequence_no' => [
                                'type' => 'integer',
                                'minimum' => 1,
                            ],
                            'ai_reason' => [
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 1000,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function normalizeTasks(array $tasks): array
    {
        $normalized = [];

        foreach ($tasks as $index => $task) {
            $normalized[] = [
                'title' => (string) ($task['title'] ?? ''),
                'description' => (string) ($task['description'] ?? ''),
                'task_category' => (string) ($task['task_category'] ?? 'planning'),
                'priority' => (string) ($task['priority'] ?? 'medium'),
                'difficulty' => (string) ($task['difficulty'] ?? 'medium'),
                'estimated_hours' => (float) ($task['estimated_hours'] ?? 1),
                'sequence_no' => (int) ($task['sequence_no'] ?? ($index + 1)),
                'ai_reason' => (string) ($task['ai_reason'] ?? ''),
            ];
        }

        usort($normalized, fn ($a, $b) => $a['sequence_no'] <=> $b['sequence_no']);

        foreach ($normalized as $i => &$task) {
            $task['sequence_no'] = $i + 1;
        }

        return $normalized;
    }
}
