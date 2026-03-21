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
        'request_payload',
        'response_payload',
        'raw_response',
        'latency_ms',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'status',
        'error_message',
        'executed_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'executed_at' => 'datetime',
    ];

    public function taskGenerationRequest(): BelongsTo
    {
        return $this->belongsTo(TaskGenerationRequest::class);
    }
}
