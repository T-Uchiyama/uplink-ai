<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiExecutionLog extends Model
{
    protected $fillable = [
        'task_generation_request_id',
        'provider',
        'model',
        'prompt_version',
        'execution_type',
        'retry_count',
        'request_payload',
        'response_payload',
        'raw_response',
        'status',
        'error_message',
        'latency_ms',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost_usd',
        'executed_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'raw_response' => 'array',
        'executed_at' => 'datetime',
        'cost_usd' => 'decimal:6',
    ];

    public function taskGenerationRequest(): BelongsTo
    {
        return $this->belongsTo(TaskGenerationRequest::class);
    }
}
