<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskGenerationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'requested_by_member_id',
        'goal',
        'available_hours',
        'previous_score',
        'note',
        'input_snapshot',
        'status',
        'requested_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'available_hours' => 'decimal:2',
            'input_snapshot' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(Member::class, 'requested_by_member_id');
    }

    public function generatedTasks()
    {
        return $this->hasMany(GeneratedTask::class, 'task_generation_request_id');
    }

    public function aiExecutionLogs()
    {
        return $this->hasMany(AiExecutionLog::class, 'request_id');
    }
}
