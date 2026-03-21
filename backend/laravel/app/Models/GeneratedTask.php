<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_generation_request_id',
        'member_id',
        'generation_version',
        'title',
        'description',
        'task_category',
        'priority',
        'difficulty',
        'start_date',
        'due_date',
        'estimated_hours',
        'status',
        'sequence_no',
        'ai_reason',
        'notion_page_id',
        'synced_to_notion_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'estimated_hours' => 'decimal:2',
            'synced_to_notion_at' => 'datetime',
        ];
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function notionSyncLogs()
    {
        return $this->hasMany(NotionSyncLog::class, 'generated_task_id');
    }

    public function taskGenerationRequest()
    {
        return $this->belongsTo(TaskGenerationRequest::class, 'task_generation_request_id');
    }
}
