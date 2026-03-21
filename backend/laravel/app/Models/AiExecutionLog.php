<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiExecutionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
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
        'estimated_cost_usd',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'retry_count' => 'integer',
            'latency_ms' => 'integer',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'estimated_cost_usd' => 'decimal:6',
            'executed_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(TaskGenerationRequest::class, 'request_id');
    }
}
