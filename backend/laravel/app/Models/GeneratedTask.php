<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedTask extends Model
{
    protected $fillable = [
        'task_generation_request_id',
        'title',
        'description',
        'task_category',
        'priority',
        'difficulty',
        'estimated_hours',
        'sequence_no',
        'ai_reason',
    ];

    protected $casts = [
        'estimated_hours' => 'float',
        'sequence_no' => 'integer',
    ];

    public function taskGenerationRequest(): BelongsTo
    {
        return $this->belongsTo(TaskGenerationRequest::class);
    }
}
