<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskGenerationRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'goal' => $this->goal,
            'available_hours' => $this->available_hours,
            'previous_score' => $this->previous_score,
            'note' => $this->note,
            'status' => $this->status,
            'generation_version' => $this->generation_version,
            'input_snapshot' => $this->input_snapshot,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'generated_tasks' => GeneratedTaskResource::collection(
                $this->whenLoaded('generatedTasks')
            ),
            'ai_execution_logs' => AiExecutionLogResource::collection(
                $this->whenLoaded('aiExecutionLogs')
            ),
        ];
    }
}
