<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneratedTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'task_category' => $this->task_category,
            'priority' => $this->priority,
            'difficulty' => $this->difficulty,
            'estimated_hours' => $this->estimated_hours,
            'sequence_no' => $this->sequence_no,
            'ai_reason' => $this->ai_reason,
            'created_at' => $this->created_at,
        ];
    }
}
