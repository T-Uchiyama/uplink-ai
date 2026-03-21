<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskGenerationRequest extends Model
{
    protected $fillable = [
        'goal',
        'available_hours',
        'previous_score',
        'note',
        'status',
        'generation_version',
        'input_snapshot',
        'requested_at',
        'completed_at',
    ];

    protected $casts = [
        'available_hours' => 'float',
        'previous_score' => 'integer',
        'generation_version' => 'integer',
        'input_snapshot' => 'array',
    ];

    public function generatedTasks(): HasMany
    {
        return $this->hasMany(GeneratedTask::class);
    }

    public function aiExecutionLogs(): HasMany
    {
        return $this->hasMany(AiExecutionLog::class, 'task_generation_request_id');
    }
}
