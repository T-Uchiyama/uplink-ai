<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotionSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'generated_task_id',
        'notion_page_id',
        'sync_type',
        'status',
        'request_payload',
        'response_payload',
        'error_message',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function generatedTask()
    {
        return $this->belongsTo(GeneratedTask::class, 'generated_task_id');
    }
}
